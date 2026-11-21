<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase2AuthenticationAndMultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful registration of a shop owner and automatic tenant creation.
     */
    public function test_shop_owner_can_register_and_create_tenant(): void
    {
        $response = $this->post('/register', [
            'owner_name' => 'Vikram Singh',
            'shop_name' => 'Singh Kirana Emporium',
            'phone' => '9876543210',
            'email' => 'vikram@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'terms' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        // Verify Tenant was created in database
        $tenant = Tenant::where('phone', '9876543210')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Singh Kirana Emporium', $tenant->name);
        $this->assertEquals('Vikram Singh', $tenant->owner_name);
        $this->assertEquals('active', $tenant->status);

        // Verify Owner User was created and associated with Tenant
        $user = User::where('phone', '9876543210')->first();
        $this->assertNotNull($user);
        $this->assertEquals('shop_owner', $user->role);
        $this->assertEquals($tenant->id, $user->tenant_id);

        // Verify Free Trial subscription was attached
        $this->assertNotNull($tenant->subscriptions()->first());
    }

    /**
     * Test validation rules on registration (invalid phone, mismatched password, etc.).
     */
    public function test_registration_validation_fails_for_invalid_input(): void
    {
        $response = $this->post('/register', [
            'owner_name' => '',
            'shop_name' => '',
            'phone' => '12345', // Invalid Indian phone (must start with 6-9 and be 10 digits)
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertSessionHasErrors(['owner_name', 'shop_name', 'phone', 'email', 'password', 'terms']);
        $this->assertGuest();
    }

    /**
     * Test login with mobile number and login with email.
     */
    public function test_user_can_login_with_mobile_or_email_and_logout(): void
    {
        $tenant = Tenant::create([
            'name' => 'Gupta Sweets',
            'slug' => 'gupta-sweets',
            'owner_name' => 'Amit Gupta',
            'phone' => '9812345678',
            'email' => 'amit@guptasweets.com',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Amit Gupta',
            'phone' => '9812345678',
            'email' => 'amit@guptasweets.com',
            'password' => Hash::make('password123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        // 1. Login with mobile number
        $loginWithPhone = $this->post('/login', [
            'login' => '9812345678',
            'password' => 'password123',
        ]);
        $loginWithPhone->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Logout
        $logout = $this->post('/logout');
        $logout->assertRedirect('/');
        $this->assertGuest();

        // 2. Login with email address
        $loginWithEmail = $this->post('/login', [
            'login' => 'amit@guptasweets.com',
            'password' => 'password123',
        ]);
        $loginWithEmail->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test registration of two separate shops and verify data isolation between them.
     */
    public function test_two_separate_shops_have_strictly_isolated_data(): void
    {
        // Shop 1
        $tenant1 = Tenant::create([
            'name' => 'Shop Alpha',
            'slug' => 'shop-alpha',
            'owner_name' => 'Owner Alpha',
            'phone' => '9800000001',
            'status' => 'active',
        ]);
        $user1 = User::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Owner Alpha',
            'phone' => '9800000001',
            'password' => Hash::make('secret1'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        // Shop 2
        $tenant2 = Tenant::create([
            'name' => 'Shop Beta',
            'slug' => 'shop-beta',
            'owner_name' => 'Owner Beta',
            'phone' => '9800000002',
            'status' => 'active',
        ]);
        $user2 = User::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Owner Beta',
            'phone' => '9800000002',
            'password' => Hash::make('secret2'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        // Create Customer in Shop 1
        $customer1 = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant1->id,
            'name' => 'Customer of Alpha',
            'phone' => '9911111111',
            'current_balance' => 1500.00,
        ]);

        // Create Customer in Shop 2
        $customer2 = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant2->id,
            'name' => 'Customer of Beta',
            'phone' => '9922222222',
            'current_balance' => 2500.00,
        ]);

        // Acting as Shop 1 Owner
        $this->actingAs($user1);
        $this->assertCount(1, Customer::all());
        $this->assertEquals('Customer of Alpha', Customer::first()->name);

        // Acting as Shop 2 Owner
        $this->actingAs($user2);
        $this->assertCount(1, Customer::all());
        $this->assertEquals('Customer of Beta', Customer::first()->name);
    }

    /**
     * Test unauthorized access protection (Guests cannot access dashboard or shop profile).
     */
    public function test_unauthenticated_guests_cannot_access_protected_routes(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/shop/profile')->assertRedirect('/login');
    }

    /**
     * Test shop profile update is strictly IDOR-safe (only updates own tenant).
     */
    public function test_shop_owner_can_only_update_their_own_profile(): void
    {
        $tenant1 = Tenant::create([
            'name' => 'First Store',
            'slug' => 'first-store',
            'owner_name' => 'First Owner',
            'phone' => '9811112222',
            'currency' => 'INR',
            'status' => 'active',
        ]);
        $user1 = User::create([
            'tenant_id' => $tenant1->id,
            'name' => 'First Owner',
            'phone' => '9811112222',
            'password' => Hash::make('pass'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Second Store',
            'slug' => 'second-store',
            'owner_name' => 'Second Owner',
            'phone' => '9833334444',
            'currency' => 'INR',
            'status' => 'active',
        ]);

        $this->actingAs($user1);

        $response = $this->post('/shop/profile', [
            'name' => 'First Store Updated',
            'owner_name' => 'First Owner Updated',
            'phone' => '9811112222',
            'currency' => 'INR',
            'address' => 'New Market Road, Delhi',
        ]);

        $response->assertRedirect('/shop/profile');
        $response->assertSessionHas('success');

        // Check Tenant 1 updated
        $this->assertEquals('First Store Updated', $tenant1->fresh()->name);
        $this->assertEquals('New Market Road, Delhi', $tenant1->fresh()->address);

        // Verify Tenant 2 remains untouched (cannot be tampered with)
        $this->assertEquals('Second Store', $tenant2->fresh()->name);
    }

    /**
     * Test suspended account status prevents login and revokes access.
     */
    public function test_suspended_tenant_cannot_login_or_access_dashboard(): void
    {
        $suspendedTenant = Tenant::create([
            'name' => 'Suspended Shop',
            'slug' => 'suspended-shop',
            'owner_name' => 'Bad Actor',
            'phone' => '9899999999',
            'status' => 'suspended',
        ]);

        $user = User::create([
            'tenant_id' => $suspendedTenant->id,
            'name' => 'Bad Actor',
            'phone' => '9899999999',
            'password' => Hash::make('password123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        // Attempting to log in should fail with suspended message
        $response = $this->post('/login', [
            'login' => '9899999999',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();

        // If user session exists but tenant gets suspended, EnsureTenantIsActive logs them out
        $this->actingAs($user);
        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertRedirect('/login');
        $this->assertGuest();
    }
}

