<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Users;

use App\Models\School;
use App\Models\User;
use App\Services\Users\IdentityEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class IdentityEmailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_the_complete_email_value(): void
    {
        $this->assertSame(
            'joao@teste.com.br',
            app(IdentityEmailService::class)->normalize('  JOAO@TESTE.COM.BR  '),
        );
    }

    public function test_it_finds_soft_deleted_legacy_owner_and_can_exclude_current_user(): void
    {
        $school = School::factory()->create();
        $owner = User::factory()->create([
            'school_id' => $school->id,
            'email' => '  Legacy.Owner@Example.Test  ',
        ]);
        $owner->delete();

        $service = app(IdentityEmailService::class);
        $decision = $service->decide('legacy.owner@example.test');

        $this->assertFalse($decision->isAvailable());
        $this->assertFalse($decision->isAmbiguous());
        $this->assertTrue($decision->owner?->is($owner));
        $this->assertTrue($service->decide('legacy.owner@example.test', $owner->id)->isAvailable());
    }

    public function test_it_marks_multiple_legacy_canonical_owners_ambiguous_with_one_lookup(): void
    {
        User::factory()->create(['email' => 'ambiguous@example.test']);
        User::factory()->create(['email' => ' ambiguous@example.test ']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $decision = app(IdentityEmailService::class)->decide('AMBIGUOUS@example.test');
        $queries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'from `users`'));

        $this->assertTrue($decision->isAmbiguous());
        $this->assertNull($decision->owner);
        $this->assertCount(1, $queries);
    }
}
