<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase8SuperAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected User $owner1;

    protected Tenant $tenant2;
    protected User $owner2;

    protected User $superAdmin;
    protected Plan $basicPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
        $this->basicPlan = Plan::where('slug', 'basic')->first();

        // Shop 1
        $this->tenant1 = Tenant::create([
            'name' => 'Kirana Mart 1',
            'slug' => 'kirana-mart-1',
            'owner_name' => 'Ramesh Kumar',
            'phone' => '9811000001',
            'email' => 'ramesh@kirana1.com',
            'status' => 'active',
        ]);
        $this->owner1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Ramesh Kumar',
            'phone' => '9811000001',
            'email' => 'ramesh@kirana1.com',
            'password' => Hash::make('secret123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        // Shop 2 (Suspended)
        $this->tenant2 = Tenant::create([
            'name' => 'Suspended Store 2',
            'slug' => 'suspended-store-2',
            'owner_name' => 'Dinesh Verma',
            'phone' => '9811000002',
            'email' => 'dinesh@store2.com',
            'status' => 'suspended',
        ]);
        $this->owner2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Dinesh Verma',
            'phone' => '9811000002',
            'email' => 'dinesh@store2.com',
            'password' => Hash::make('secret123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        // Super Admin User
        $this->superAdmin = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Root Super Admin',
            'phone' => '9999999999',
            'email' => 'superadmin@udhaarhisab.in',
            'password' => Hash::make('adminpassword123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_guests_cannot_access_super_admin(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_shop_owner_is_forbidden_from_admin_panel(): void
    {
        $response = $this->actingAs($this->owner1)->get(route('admin.dashboard'));
        $response->assertStatus(403);

        $shopsResponse = $this->actingAs($this->owner1)->get(route('admin.shops.index'));
        $shopsResponse->assertStatus(403);
    }

    public function test_super_admin_can_access_dashboard_with_accurate_metrics(): void
    {
        // Give Shop 1 an active paid basic subscription (₹99)
        Subscription::create([
            'tenant_id' => $this->tenant1->id,
            'plan_id' => $this->basicPlan->id,
            'starts_at' => Carbon::today(),
            'ends_at' => Carbon::today()->addDays(30),
            'status' => 'active',
        ]);

        // Record a successful payment of ₹99.00
        SubscriptionPayment::create([
            'tenant_id' => $this->tenant1->id,
            'plan_id' => $this->basicPlan->id,
            'amount' => 99.00,
            'currency' => 'INR',
            'payment_method' => 'razorpay',
            'gateway_order_id' => 'order_test_99',
            'gateway_payment_id' => 'pay_test_99',
            'status' => 'successful',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');

        $this->assertEquals(2, $response->viewData('totalShops'));
        $this->assertEquals(1, $response->viewData('activeShops'));
        $this->assertEquals(1, $response->viewData('suspendedShops'));
        $this->assertEquals(99.00, (float) $response->viewData('mrr'));
        $this->assertEquals(99.00, (float) $response->viewData('totalRevenue'));
    }

    public function test_super_admin_can_search_and_view_all_shops(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.shops.index', [
            'search' => 'Kirana Mart',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Kirana Mart 1');
        $response->assertDontSee('Suspended Store 2');

        // View single shop
        $detailResponse = $this->actingAs($this->superAdmin)->get(route('admin.shops.show', $this->tenant1));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Kirana Mart 1');
        $detailResponse->assertSee('Ramesh Kumar');
    }

    public function test_super_admin_can_toggle_shop_status_and_logs_activity(): void
    {
        // Suspend active shop 1
        $response = $this->actingAs($this->superAdmin)->post(route('admin.shops.toggle_status', $this->tenant1), [
            'reason' => 'Suspected fraudulent activity',
        ]);

        $response->assertStatus(302);
        $this->tenant1->refresh();
        $this->assertEquals('suspended', $this->tenant1->status);

        // Assert audit log was recorded
        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'shop_status_updated',
            'target_type' => 'Tenant',
            'target_id' => $this->tenant1->id,
        ]);

        // Activate suspended shop 2
        $this->actingAs($this->superAdmin)->post(route('admin.shops.toggle_status', $this->tenant2));
        $this->tenant2->refresh();
        $this->assertEquals('active', $this->tenant2->status);
    }

    public function test_super_admin_can_create_new_plan_and_safe_deletion(): void
    {
        // 1. Create a new plan
        $response = $this->actingAs($this->superAdmin)->post(route('admin.plans.store'), [
            'name' => 'Wholesaler Plan',
            'slug' => 'wholesaler',
            'price' => '499.00',
            'duration_days' => 30,
            'customer_limit' => 2000,
            'description' => 'For large wholesale traders with high volume ledger.',
            'features' => [
                'pdf_statements' => 1,
                'whatsapp_reminders' => 1,
                'csv_exports' => 1,
                'advanced_reports' => 1,
                'priority_support' => 1,
            ],
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseHas('plans', [
            'slug' => 'wholesaler',
            'price' => '499.00',
            'customer_limit' => 2000,
        ]);

        $createdPlan = Plan::where('slug', 'wholesaler')->first();

        // 2. Safe deletion: plan with 0 subscribers can be deleted
        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('admin.plans.destroy', $createdPlan));
        $deleteResponse->assertRedirect(route('admin.plans.index'));
        $this->assertDatabaseMissing('plans', ['slug' => 'wholesaler']);

        // 3. Plan with active subscriptions CANNOT be deleted
        Subscription::create([
            'tenant_id' => $this->tenant1->id,
            'plan_id' => $this->basicPlan->id,
            'starts_at' => Carbon::today(),
            'ends_at' => Carbon::today()->addDays(30),
            'status' => 'active',
        ]);

        $blockedDelete = $this->actingAs($this->superAdmin)->delete(route('admin.plans.destroy', $this->basicPlan));
        $this->assertDatabaseHas('plans', ['id' => $this->basicPlan->id]);
    }

    public function test_super_admin_can_view_payments_and_process_refund(): void
    {
        $payment = SubscriptionPayment::create([
            'tenant_id' => $this->tenant1->id,
            'plan_id' => $this->basicPlan->id,
            'amount' => 99.00,
            'currency' => 'INR',
            'payment_method' => 'razorpay',
            'gateway_order_id' => 'order_ref_101',
            'gateway_payment_id' => 'pay_ref_101',
            'status' => 'successful',
        ]);

        // View payments list
        $response = $this->actingAs($this->superAdmin)->get(route('admin.payments.index'));
        $response->assertStatus(200);
        $response->assertSee('pay_ref_101');
        $response->assertSee('Kirana Mart 1');

        // Process refund
        $refundResponse = $this->actingAs($this->superAdmin)->post(route('admin.payments.refund', $payment), [
            'refund_reason' => 'Accidental double payment by shop owner',
        ]);

        $refundResponse->assertStatus(302);
        $payment->refresh();

        $this->assertEquals('refunded', $payment->refund_status);
        $this->assertNotNull($payment->refunded_at);

        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'payment_refunded',
            'target_type' => 'SubscriptionPayment',
            'target_id' => $payment->id,
        ]);
    }

    public function test_super_admin_can_update_platform_settings(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.update'), [
            'site_name' => 'UdhaarHisab Pro India',
            'contact_email' => 'help@udhaarhisab.in',
            'contact_phone' => '+91 88000 11223',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'trial_days' => 45,
            'support_hours' => '24x7 Digital Assistance',
        ]);

        $response->assertRedirect(route('admin.settings.index'));

        $this->assertEquals('UdhaarHisab Pro India', PlatformSetting::get('site_name'));
        $this->assertEquals('help@udhaarhisab.in', PlatformSetting::get('contact_email'));
        $this->assertEquals('45', PlatformSetting::get('trial_days'));

        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'platform_settings_updated',
        ]);
    }
}

