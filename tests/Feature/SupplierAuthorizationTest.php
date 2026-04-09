<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_authenticated_users_can_manage_suppliers_when_mode_is_all(): void
    {
        config()->set('suppliers.access_mode', 'all');
        config()->set('suppliers.allowed_emails', []);

        $user = User::factory()->create(['email' => 'anyone@example.com']);

        $this->actingAs($user)
            ->get(route('suppliers.index'))
            ->assertOk();
    }

    public function test_only_configured_emails_can_manage_suppliers_when_mode_is_emails(): void
    {
        config()->set('suppliers.access_mode', 'emails');
        config()->set('suppliers.allowed_emails', ['allowed@example.com']);

        $allowedUser = User::factory()->create(['email' => 'allowed@example.com']);
        $blockedUser = User::factory()->create(['email' => 'blocked@example.com']);

        $this->actingAs($allowedUser)
            ->get(route('suppliers.index'))
            ->assertOk();

        $this->actingAs($blockedUser)
            ->get(route('suppliers.index'))
            ->assertForbidden();
    }
}
