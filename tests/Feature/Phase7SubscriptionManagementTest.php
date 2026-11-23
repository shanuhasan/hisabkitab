<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase7SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $owner;
    protected User $superAdmin;
    protected SubscriptionService $subscriptionService;

    protected Plan $freePlan;
    protected Plan $basicPlan;
    protected Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);

        $this->freePlan = Plan::where('slug', 'free')->first();
        $this->basicPlan = Plan::where('slug', 'basic')->first();
        $this->proPlan = Plan::where('slug', 'pro')->first();

        $this->subscriptionService = app(SubscriptionService::class);

        // Setup Shop Tenant
        $this->tenant = Tenant::create([
            'name' => 'Agrawal Grocers',
            'slug' => 'agrawal-grocers',
            'owner_name' => 'Alok Agrawal',
            'phone' => '9811999001',
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Alok Agrawal',
            'phone' => '9811999001',
            'email' => 'alok@agrawalstore.com',
            'password' => Hash::make('secret123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        // Setup Super Admin User
        $this->superAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Platform Super Admin',
            'phone' => '9999999999',
            'email' => 'admin@udhaarhisab.com',
            'password' => Hash::make('admin123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_plans_are_seeded_with_exact_limits_and_pricing(): void
    {
        $this->assertEquals(0.00, (float) $this->freePlan->price);
        $this->assertEquals(25, $this->freePlan->customer_limit);

        $this->assertEquals(99.00, (float) $this->basicPlan->price);
        $this->assertEquals(200, $this->basicPlan->customer_limit);
        $this->assertTrue($this->basicPlan->hasFeature('pdf_statements'));
        $this->assertFalse($this->basicPlan->hasFeature('csv_exports'));

        $this->assertEquals(199.00, (float) $this->proPlan->price);
        $this->assertEquals(-1, $this->proPlan->customer_limit);
        $this->assertTrue($this->proPlan->hasFeature('csv_exports'));
        $this->assertTrue($this->proPlan->hasFeature('advanced_reports'));
    }

    public function test_shop_owner_can_view_subscription_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('subscription.index'));

        $response->assertStatus(200);
        $response->assertViewIs('subscription.index');
        $response->assertSee('Free Plan');
        $response->assertSee('Basic Plan');
        $response->assertSee('Pro Plan');
        $response->assertSee('99');
        $response->assertSee('199');
    }

    public function test_razorpay_order_creation_stores_pending_payment(): void
    {
        $response = $this->actingAs($this->owner)->postJson(route('subscription.checkout.order'), [
            'plan_id' => $this->basicPlan->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'order' => ['order_id', 'amount', 'currency', 'key_id', 'plan_id'],
        ]);

        $orderId = $response->json('order.order_id');
        $this->assertEquals(9900, $response->json('order.amount')); // 99.00 in paise

        $this->assertDatabaseHas('subscription_payments', [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->basicPlan->id,
            'gateway_order_id' => $orderId,
            'status' => 'pending',
        ]);
    }

    public function test_server_side_payment_signature_verification_activates_subscription(): void
    {
        // 1. Create order
        $orderData = $this->subscriptionService->createRazorpayOrder($this->tenant, $this->basicPlan);
        $orderId = $orderData['order_id'];
        $paymentId = 'pay_test_' . bin2hex(random_bytes(6));

        // 2. Generate valid HMAC-SHA256 signature
        $secret = config('services.razorpay.secret', env('RAZORPAY_KEY_SECRET', 'test_secret_udhaarhisab_123'));
        $validSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);

        // 3. Post verification
        $response = $this->actingAs($this->owner)->postJson(route('subscription.checkout.verify'), [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $validSignature,
            'plan_id' => $this->basicPlan->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Verify payment marked successful
        $this->assertDatabaseHas('subscription_payments', [
            'tenant_id' => $this->tenant->id,
            'gateway_order_id' => $orderId,
            'gateway_payment_id' => $paymentId,
            'status' => 'successful',
        ]);

        // Verify subscription activated
        $sub = Subscription::where('tenant_id', $this->tenant->id)->latest('id')->first();
        $this->assertEquals($this->basicPlan->id, $sub->plan_id);
        $this->assertEquals('active', $sub->status);
        $this->assertTrue($sub->ends_at->isFuture());
    }

    public function test_invalid_payment_signature_is_rejected_and_never_activates_subscription(): void
    {
        $orderData = $this->subscriptionService->createRazorpayOrder($this->tenant, $this->proPlan);
        $orderId = $orderData['order_id'];
        $paymentId = 'pay_fraud_123';
        $invalidSignature = 'fake_signature_attempt_654321';

        $response = $this->actingAs($this->owner)->postJson(route('subscription.checkout.verify'), [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $invalidSignature,
            'plan_id' => $this->proPlan->id,
        ]);

        $response->assertStatus(422);

        // Payment must be recorded as failed
        $this->assertDatabaseHas('subscription_payments', [
            'tenant_id' => $this->tenant->id,
            'gateway_order_id' => $orderId,
            'status' => 'failed',
        ]);

        // Tenant must NOT be upgraded to Pro
        $sub = $this->subscriptionService->getCurrentSubscription($this->tenant);
        $this->assertNotEquals($this->proPlan->id, $sub->plan_id);
    }

    public function test_webhook_processing_with_idempotency(): void
    {
        $orderData = $this->subscriptionService->createRazorpayOrder($this->tenant, $this->proPlan);
        $orderId = $orderData['order_id'];
        $paymentId = 'pay_webhook_999';

        $payloadArray = [
            'id' => 'evt_1234567890',
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $paymentId,
                        'order_id' => $orderId,
                        'amount' => 19900,
                    ],
                ],
            ],
        ];
        $payloadJson = json_encode($payloadArray);

        $webhookSecret = config('services.razorpay.webhook_secret', env('RAZORPAY_WEBHOOK_SECRET', 'test_webhook_secret_123'));
        $signature = hash_hmac('sha256', $payloadJson, $webhookSecret);

        // 1. First webhook delivery
        $response = $this->call(
            'POST',
            route('webhook.razorpay'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            ],
            $payloadJson
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Assert subscription upgraded to Pro
        $sub = Subscription::where('tenant_id', $this->tenant->id)->latest('id')->first();
        $this->assertEquals($this->proPlan->id, $sub->plan_id);

        // 2. Duplicate webhook delivery (Idempotency test)
        $duplicateResponse = $this->call(
            'POST',
            route('webhook.razorpay'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            ],
            $payloadJson
        );

        $duplicateResponse->assertStatus(200);
        $duplicateResponse->assertJson(['status' => 'ignored']);
    }

    public function test_customer_limit_enforced_under_free_plan(): void
    {
        // Explicitly set tenant on Free Plan (limit 25)
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->freePlan->id,
            'starts_at' => Carbon::today(),
            'ends_at' => Carbon::today()->addDays(365),
            'status' => 'active',
        ]);

        // Create 25 customers
        for ($i = 1; $i <= 25; $i++) {
            Customer::create([
                'tenant_id' => $this->tenant->id,
                'name' => "Customer {$i}",
                'phone' => sprintf('98000000%02d', $i),
                'current_balance' => '0.00',
            ]);
        }

        $this->assertFalse($this->subscriptionService->canAddCustomer($this->tenant));

        // Attempting to add 26th customer via HTTP must fail validation
        $response = $this->actingAs($this->owner)->post(route('customers.store'), [
            'name' => 'Customer 26 Excess',
            'phone' => '9800000099',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('customers', ['name' => 'Customer 26 Excess']);
    }

    public function test_feature_restrictions_for_free_and_pro_plans(): void
    {
        // 1. Free plan tenant
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->freePlan->id,
            'starts_at' => Carbon::today(),
            'ends_at' => Carbon::today()->addDays(365),
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Grahak',
            'phone' => '9822334455',
            'current_balance' => '100.00',
        ]);

        // Free plan cannot download PDF statements (403)
        $statementResponse = $this->actingAs($this->owner)->get(route('customers.statement.pdf', $customer));
        $statementResponse->assertStatus(403);

        // Free plan cannot export CSV (403)
        $csvResponse = $this->actingAs($this->owner)->get(route('reports.export.csv'));
        $csvResponse->assertStatus(403);

        // 2. Upgrade to Pro Plan
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->proPlan->id,
            'starts_at' => Carbon::today(),
            'ends_at' => Carbon::today()->addDays(30),
            'status' => 'active',
        ]);

        // Pro plan can download PDF statements
        $proStatementResponse = $this->actingAs($this->owner)->get(route('customers.statement.pdf', $customer));
        $proStatementResponse->assertStatus(200);

        // Pro plan can export CSV
        $proCsvResponse = $this->actingAs($this->owner)->get(route('reports.export.csv'));
        $proCsvResponse->assertStatus(200);
    }

    public function test_super_admin_can_update_plan_prices_and_limits(): void
    {
        $response = $this->actingAs($this->superAdmin)->put(route('admin.plans.update', $this->basicPlan), [
            'name' => 'Basic Plan Modified',
            'price' => '129.00',
            'duration_days' => 30,
            'customer_limit' => 250,
            'features' => [
                'pdf_statements' => 1,
                'whatsapp_reminders' => 1,
                'csv_exports' => 0,
            ],
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.plans.index'));

        $this->basicPlan->refresh();
        $this->assertEquals('129.00', (string) $this->basicPlan->price);
        $this->assertEquals(250, $this->basicPlan->customer_limit);
        $this->assertEquals('Basic Plan Modified', $this->basicPlan->name);
    }
}

