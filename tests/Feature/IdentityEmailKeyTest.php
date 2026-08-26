<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class IdentityEmailKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_identity_email_key_canonicalizes_existing_email_without_rewriting_it(): void
    {
        $user = User::factory()->create(['email' => '  Legacy.User@Example.Test  ']);

        $this->assertTrue(Schema::hasColumn('users', 'identity_email_key'));
        $this->assertSame('  Legacy.User@Example.Test  ', $user->refresh()->email);
        $this->assertSame('legacy.user@example.test', $user->identity_email_key);
    }

    public function test_mysql_uses_the_identity_email_key_index_for_retained_owner_lookup(): void
    {
        User::factory()->create(['email' => 'indexed@example.test']);

        $explain = DB::selectOne(
            'EXPLAIN SELECT id FROM users WHERE identity_email_key = ? LIMIT 2',
            ['indexed@example.test'],
        );

        $this->assertSame('users_identity_email_key_index', $explain->key);
    }
}
