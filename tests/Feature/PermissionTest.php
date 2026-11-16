<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sales_agent_cannot_access_user_management(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('Sales Agent');

        $this->actingAs($agent)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_super_administrator_can_access_user_management(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Administrator');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_auditor_can_view_users_but_cannot_create(): void
    {
        $auditor = User::factory()->create();
        $auditor->assignRole('Auditor');

        $this->actingAs($auditor)
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->get(route('admin.users.create'))
            ->assertForbidden();
    }
}
