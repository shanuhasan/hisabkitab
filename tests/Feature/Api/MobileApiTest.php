<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    protected function registerShop(string $phone = '9876500001'): array
    {
        return $this->postJson('/api/v1/auth/register', [
            'owner_name' => 'Ramesh Kumar',
            'shop_name' => 'Ramesh Kirana',
            'phone' => $phone,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms' => true,
            'device_name' => 'test-phone',
        ])->assertCreated()->json();
    }

    protected function makeOwner(string $phone, string $status = 'active', string $role = 'shop_owner'): User
    {
        $tenant = Tenant::create([
            'name' => "Shop {$phone}", 'slug' => "shop-{$phone}", 'owner_name' => 'Owner',
            'phone' => $phone, 'status' => $status,
        ]);

        return User::create([
            'tenant_id' => $tenant->id, 'name' => 'Owner', 'phone' => $phone,
            'password' => Hash::make('secret123'), 'role' => $role, 'is_active' => true,
        ]);
    }

    public function test_register_returns_token_that_works(): void
    {
        $res = $this->registerShop();

        $this->assertNotEmpty($res['token']);
        $this->assertSame('Ramesh Kirana', $res['shop']['name']);
        $this->assertSame('shop_owner', $res['user']['role']);

        $this->withToken($res['token'])->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('shop.name', 'Ramesh Kirana')
            ->assertJsonPath('subscription.is_valid', true);
    }

    public function test_login_with_phone_and_logout_revokes_token(): void
    {
        $this->registerShop('9876500002');

        $token = $this->postJson('/api/v1/auth/login', ['login' => '9876500002', 'password' => 'secret123'])
            ->assertOk()
            ->json('token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1); // only the register token remains

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_wrong_password_and_missing_token_give_json_errors(): void
    {
        $this->registerShop('9876500003');

        $this->postJson('/api/v1/auth/login', ['login' => '9876500003', 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('login');

        $this->get('/api/v1/dashboard')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_super_admin_and_suspended_shop_cannot_use_app(): void
    {
        $this->makeOwner('9811100001', 'active', 'super_admin');
        $this->postJson('/api/v1/auth/login', ['login' => '9811100001', 'password' => 'secret123'])
            ->assertStatus(422)
            ->assertJsonFragment(['The mobile app is for shop owners and staff. Please use the Admin Panel on the website.']);

        $suspended = $this->makeOwner('9811100002', 'suspended');
        $this->postJson('/api/v1/auth/login', ['login' => '9811100002', 'password' => 'secret123'])
            ->assertStatus(422);

        // A token issued before suspension stops working too
        Sanctum::actingAs($suspended);
        $this->getJson('/api/v1/dashboard')->assertForbidden()->assertJsonPath('code', 'shop_suspended');
    }

    public function test_full_shopkeeper_flow_customer_udhaar_jama_reverse(): void
    {
        $token = $this->registerShop('9876500004')['token'];
        $api = fn () => $this->withToken($token);

        // Add customer with ₹500 purana udhaar
        $customerId = $api()->postJson('/api/v1/customers', [
            'name' => 'Suresh', 'phone' => '9123456780',
            'opening_balance' => 500, 'opening_balance_type' => 'credit',
        ])->assertCreated()
            ->assertJsonPath('data.balance', '500.00')
            ->assertJsonPath('data.balance_status', 'due')
            ->json('data.id');

        // Give ₹250 more udhaar
        $api()->postJson('/api/v1/transactions', [
            'customer_id' => $customerId, 'type' => 'credit', 'amount' => 250,
            'transaction_date' => now()->toDateString(), 'description' => 'Atta, dal',
        ])->assertCreated()->assertJsonPath('customer.balance', '750.00');

        // Receive ₹300
        $paymentId = $api()->postJson('/api/v1/transactions', [
            'customer_id' => $customerId, 'type' => 'payment', 'amount' => 300,
            'transaction_date' => now()->toDateString(), 'payment_mode' => 'upi',
        ])->assertCreated()
            ->assertJsonPath('data.type_label', 'Jama Mila')
            ->assertJsonPath('customer.balance', '450.00')
            ->json('data.id');

        // Paying more than due without allow_advance is refused
        $this->travel(5)->seconds();
        $api()->postJson('/api/v1/transactions', [
            'customer_id' => $customerId, 'type' => 'payment', 'amount' => 9999,
            'transaction_date' => now()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('amount');

        // Reverse the ₹300 payment → balance back to ₹750
        $api()->postJson("/api/v1/transactions/{$paymentId}/reverse", ['reason' => 'Galat entry ho gayi'])
            ->assertOk()
            ->assertJsonPath('customer.balance', '750.00');

        $api()->postJson("/api/v1/transactions/{$paymentId}/reverse", ['reason' => 'Dobara try'])
            ->assertStatus(422);

        // Ledger, list, dashboard
        $api()->getJson("/api/v1/customers/{$customerId}/transactions")
            ->assertOk()->assertJsonCount(4, 'data');

        $api()->getJson('/api/v1/customers?filter=due')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('summary.total_due', '750.00');

        $api()->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('totals.total_due', '750.00')
            ->assertJsonPath('totals.total_customers', 1);

        // WhatsApp reminder
        $api()->getJson("/api/v1/customers/{$customerId}/reminder")
            ->assertOk()->assertJsonCount(4, 'templates');
        $url = $api()->postJson("/api/v1/customers/{$customerId}/reminder", ['template' => 'standard'])
            ->assertOk()->json('whatsapp_url');
        $this->assertStringStartsWith('https://wa.me/919123456780?text=', $url);
        $this->assertDatabaseHas('whatsapp_logs', ['customer_id' => $customerId]);

        // Archive & restore
        $api()->deleteJson("/api/v1/customers/{$customerId}")->assertOk();
        $api()->getJson('/api/v1/customers')->assertJsonCount(0, 'data');
        $api()->postJson("/api/v1/customers/{$customerId}/restore")->assertOk();
    }

    public function test_shop_cannot_see_another_shops_customer(): void
    {
        $tokenA = $this->registerShop('9876500005')['token'];
        $tokenB = $this->registerShop('9876500006')['token'];

        $customerId = $this->withToken($tokenA)->postJson('/api/v1/customers', [
            'name' => 'Private', 'phone' => '9123456781',
        ])->assertCreated()->json('data.id');

        $this->app['auth']->forgetGuards();

        $this->withToken($tokenB)->getJson("/api/v1/customers/{$customerId}")->assertNotFound();
        $this->withToken($tokenB)->postJson('/api/v1/transactions', [
            'customer_id' => $customerId, 'type' => 'credit', 'amount' => 10,
            'transaction_date' => now()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('customer_id');
        $this->withToken($tokenB)->getJson('/api/v1/customers')->assertJsonCount(0, 'data');
        $this->assertSame(1, Customer::withoutGlobalScopes()->count());
    }

    public function test_staff_cannot_reverse_or_edit_shop(): void
    {
        $owner = $this->makeOwner('9811100003');
        $staff = User::create([
            'tenant_id' => $owner->tenant_id, 'name' => 'Staff', 'phone' => '9811100004',
            'password' => Hash::make('secret123'), 'role' => 'shop_staff', 'is_active' => true,
        ]);

        Sanctum::actingAs($owner);
        $customerId = $this->postJson('/api/v1/customers', ['name' => 'C', 'phone' => '9123456782'])->json('data.id');
        $txnId = $this->postJson('/api/v1/transactions', [
            'customer_id' => $customerId, 'type' => 'credit', 'amount' => 100,
            'transaction_date' => now()->toDateString(),
        ])->json('data.id');

        Sanctum::actingAs($staff);
        $this->postJson("/api/v1/transactions/{$txnId}/reverse", ['reason' => 'Staff try karta'])->assertForbidden();
        $this->putJson('/api/v1/shop', [
            'name' => 'X', 'owner_name' => 'Y', 'phone' => '9811100004', 'currency' => 'INR',
        ])->assertForbidden();
        $this->getJson('/api/v1/shop')->assertOk();
    }
}
