<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\DTOs\TenantContext;
use App\DTOs\Users\CreateUserData;
use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Users\IdentityEmailService;
use App\Services\Users\UserService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PDOException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class UserDuplicateEmailConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    protected array $connectionsToTransact = [];

    public function test_only_the_users_email_unique_index_is_classified_as_an_email_race(): void
    {
        $service = app(IdentityEmailService::class);
        $emailViolation = $this->uniqueViolation('users_email_unique');
        $unrelatedViolation = $this->uniqueViolation('users_uuid_unique');

        $this->assertTrue($service->isEmailUniqueViolation($emailViolation));
        $this->assertFalse($service->isEmailUniqueViolation($unrelatedViolation));
        $this->assertFalse($service->isEmailUniqueViolation(new PDOException('unrelated database failure')));
    }

    public function test_user_creation_rethrows_an_unrelated_unique_index_violation(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();
        $thrown = null;

        User::creating(function (): never {
            throw $this->uniqueViolation('users_uuid_unique');
        });

        try {
            app(UserService::class)->create(
                $actor,
                new TenantContext($school, 'test-unrelated-index', 'resolved'),
                new CreateUserData('Unrelated Index', 'unrelated-index@example.test', [$role->uuid]),
            );
        } catch (UniqueConstraintViolationException $exception) {
            $thrown = $exception;
        } finally {
            Event::forget('eloquent.creating: '.User::class);
            User::clearBootedModels();
            DB::purge('mysql');
            Artisan::call('migrate:fresh', ['--force' => true]);
        }

        $this->assertInstanceOf(UniqueConstraintViolationException::class, $thrown);
        $this->assertSame('users_uuid_unique', $thrown?->index);
    }

    public function test_two_connection_race_commits_one_identity_and_translates_loser_after_rollback(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();
        $email = 'race-'.Str::lower((string) Str::uuid()).'@example.test';
        $barrier = sys_get_temp_dir().'/schoolmaster-email-race-'.Str::uuid();
        $readyPath = $barrier.'.ready';
        $goPath = $barrier.'.go';
        $resultPath = $barrier.'.result';
        $workerPath = $barrier.'.php';

        config([
            'database.connections.race_parent' => config('database.connections.mysql'),
            'database.connections.race_observer' => config('database.connections.mysql'),
        ]);
        $parent = DB::connection('race_parent');
        $parent->beginTransaction();
        file_put_contents($workerPath, $this->raceWorkerSource());
        $process = new Process([
            PHP_BINARY,
            $workerPath,
            base_path(),
            $email,
            (string) $actor->id,
            (string) $school->id,
            $role->uuid,
            $readyPath,
            $goPath,
            $resultPath,
        ]);
        $process->setTimeout(20);
        $process->start();

        try {
            $this->waitForFile($readyPath);
            $this->assertSame('available', file_get_contents($readyPath));

            $parent->table('users')->insert([
                'uuid' => (string) Str::uuid(),
                'school_id' => $school->id,
                'name' => 'Race Winner',
                'full_name' => 'Race Winner',
                'email' => $email,
                'password' => Str::password(32),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            file_put_contents($goPath, 'go');

            $this->waitForBlockedUserInsert();
            $parent->commit();
            $process->wait();

            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $result = json_decode((string) file_get_contents($resultPath), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(['email' => ['The email is unavailable.']], $result['errors'] ?? null);
            $this->assertSame(1, User::withTrashed()->where('identity_email_key', $email)->count());

            $audit = AuditEvent::query()->where('event_type', 'user_creation_duplicate_email')->sole();
            $this->assertSame('validation_failed', $audit->outcome);
            $this->assertSame('persistence_conflict', $audit->tenant_safe_metadata['reason_code']);
            $this->assertNull($audit->affected_resource_id);
            $winner = User::withTrashed()->where('identity_email_key', $email)->sole();
            $this->assertDatabaseMissing('role_user', ['role_id' => $role->id, 'user_id' => $winner->id]);
        } finally {
            if ($parent->transactionLevel() > 0) {
                $parent->rollBack();
            }

            if ($process->isRunning()) {
                $process->stop();
            }

            foreach ([$readyPath, $goPath, $resultPath, $workerPath] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }

            DB::purge('race_parent');
            DB::purge('race_observer');
            DB::purge('mysql');
            Artisan::call('migrate:fresh', ['--force' => true]);
        }
    }

    private function uniqueViolation(string $index): UniqueConstraintViolationException
    {
        return (new UniqueConstraintViolationException(
            'mysql',
            'insert into users (email) values (?)',
            ['duplicate@example.test'],
            new PDOException('Duplicate entry', 23000),
        ))->setIndex($index);
    }

    private function waitForFile(string $path): void
    {
        $deadline = microtime(true) + 10;
        while (! is_file($path) && microtime(true) < $deadline) {
            usleep(10_000);
        }

        if (! is_file($path)) {
            throw new \RuntimeException("Timed out waiting for race barrier: {$path}");
        }
    }

    private function waitForBlockedUserInsert(): void
    {
        $observer = DB::connection('race_observer');
        $deadline = microtime(true) + 10;

        do {
            $blocked = collect($observer->select('SHOW PROCESSLIST'))
                ->contains(fn (object $process): bool => is_string($process->Info)
                    && str_contains(strtolower($process->Info), 'insert into `users`'));
            if ($blocked) {
                return;
            }
            usleep(10_000);
        } while (microtime(true) < $deadline);

        throw new \RuntimeException('Timed out waiting for the competing users insert.');
    }

    private function raceWorkerSource(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[$email, $actorId, $schoolId, $roleUuid, $readyPath, $goPath, $resultPath] = array_slice($argv, 2);

try {
    $available = app(App\Services\Users\IdentityEmailService::class)->decide($email)->isAvailable();
    file_put_contents($readyPath, $available ? 'available' : 'occupied');

    $deadline = microtime(true) + 10;
    while (! is_file($goPath) && microtime(true) < $deadline) {
        usleep(10_000);
    }

    $actor = App\Models\User::query()->findOrFail((int) $actorId);
    $school = App\Models\School::query()->findOrFail((int) $schoolId);
    app(App\Services\Users\UserService::class)->create(
        $actor,
        new App\DTOs\TenantContext($school, 'test-race', 'resolved'),
        new App\DTOs\Users\CreateUserData('Race Loser', $email, [$roleUuid]),
        '127.0.0.9',
    );
    file_put_contents($resultPath, json_encode(['created' => true], JSON_THROW_ON_ERROR));
} catch (Illuminate\Validation\ValidationException $exception) {
    file_put_contents($resultPath, json_encode(['errors' => $exception->errors()], JSON_THROW_ON_ERROR));
} catch (Throwable $exception) {
    file_put_contents($resultPath, json_encode(['exception' => $exception::class], JSON_THROW_ON_ERROR));
}
PHP;
    }
}
