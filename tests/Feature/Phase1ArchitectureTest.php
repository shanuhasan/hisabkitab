<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1ArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_loads_with_guest_layout(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Hisab');
        $response->assertSee('Kitab');
        $response->assertSee('Digital Udhaar', false);
        $response->assertSee('Jama Khata', false);
    }

    public function test_tenant_and_user_creation_with_role_helpers(): void
    {
        $tenant = Tenant::create([
            'name' => 'Patel Store',
            'slug' => 'patel-store',
            'owner_name' => 'Rajesh Patel',
            'phone' => '9876543210',
            'email' => 'patel@example.com',
            'status' => 'active',
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Rajesh Patel',
            'email' => 'owner@patelstore.com',
            'password' => bcrypt('password123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        $superAdmin = User::create([
            'tenant_id' => null,
            'name' => 'Platform Admin',
            'email' => 'admin@hisabkitab.test',
            'password' => bcrypt('admin123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->assertTrue($owner->isShopOwner());
        $this->assertFalse($owner->isSuperAdmin());
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertEquals($tenant->id, $owner->tenant->id);
    }

    public function test_tenant_isolation_via_tenant_scope(): void
    {
        // Create Tenant 1
        $tenant1 = Tenant::create([
            'name' => 'Shop 1',
            'slug' => 'shop-1',
            'owner_name' => 'Owner 1',
            'phone' => '9811111111',
        ]);
        $user1 = User::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Owner 1',
            'email' => 'owner1@test.com',
            'password' => bcrypt('password'),
            'role' => 'shop_owner',
        ]);

        // Create Tenant 2
        $tenant2 = Tenant::create([
            'name' => 'Shop 2',
            'slug' => 'shop-2',
            'owner_name' => 'Owner 2',
            'phone' => '9822222222',
        ]);
        $user2 = User::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Owner 2',
            'email' => 'owner2@test.com',
            'password' => bcrypt('password'),
            'role' => 'shop_owner',
        ]);

        // Create Customers for both
        $customer1 = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant1->id,
            'name' => 'Customer of Shop 1',
            'phone' => '9900000001',
            'current_balance' => 500.00,
        ]);

        $customer2 = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant2->id,
            'name' => 'Customer of Shop 2',
            'phone' => '9900000002',
            'current_balance' => 1200.00,
        ]);

        // Act as User 1: Should only see Customer 1
        $this->actingAs($user1);
        $customersForUser1 = Customer::all();
        $this->assertCount(1, $customersForUser1);
        $this->assertEquals($customer1->id, $customersForUser1->first()->id);

        // Act as User 2: Should only see Customer 2
        $this->actingAs($user2);
        $customersForUser2 = Customer::all();
        $this->assertCount(1, $customersForUser2);
        $this->assertEquals($customer2->id, $customersForUser2->first()->id);
    }
}
