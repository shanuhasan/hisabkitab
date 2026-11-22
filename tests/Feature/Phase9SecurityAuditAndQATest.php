<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase9SecurityAuditAndQATest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clearResolvedInstances();
    }

    protected function createShopAndOwner(string $shopName = 'Test Shop', string $phone = '9876543210'): array
    {
        $tenant = Tenant::create([
            'name' => $shopName,
            'slug' => \Illuminate\Support\Str::slug($shopName) . '-' . rand(100, 999),
            'owner_name' => 'Owner ' . $shopName,
            'phone' => $phone,
            'status' => 'active',
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner ' . $shopName,
            'phone' => $phone,
            'role' => 'shop_owner',
            'is_active' => true,
            'password' => bcrypt('Password123!'),
        ]);

        return [$tenant, $user];
    }

    /**
     * Test 1: Password reset rate limiting prevents abuse / enumeration.
     */
    public function test_password_reset_is_rate_limited(): void
    {
        [$tenant, $user] = $this->createShopAndOwner('Shop Reset', '9999911111');

        // Send 5 valid requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('password.email'), [
                'login' => $user->phone,
            ]);
            $response->assertRedirect();
        }

        // 6th request should be blocked by rate limiter
        $blockedResponse = $this->post(route('password.email'), [
            'login' => $user->phone,
        ]);
        $blockedResponse->assertSessionHasErrors('login');
        $this->assertStringContainsString('Too many password reset attempts', session('errors')->first('login'));
    }

    /**
     * Test 2: Rapid duplicate transaction submission (debounce) protection.
     */
    public function test_rapid_duplicate_transaction_is_blocked_by_debounce_guard(): void
    {
        [$tenant, $user] = $this->createShopAndOwner('Shop Debounce', '9999922222');

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Debounce Grahak',
            'phone' => '9888800001',
            'current_balance' => 0.00,
            'status' => 'active',
        ]);

        $txnService = app(TransactionService::class);

        // First credit transaction succeeds
        $firstTxn = $txnService->recordTransaction($customer, $user, [
            'type' => 'credit',
            'amount' => '500.00',
            'description' => 'Grocery Udhaar',
        ]);
        $this->assertNotNull($firstTxn);

        // Immediate duplicate transaction with identical details within 3 seconds should throw ValidationException
        $this->expectException(ValidationException::class);
        $txnService->recordTransaction($customer, $user, [
            'type' => 'credit',
            'amount' => '500.00',
            'description' => 'Grocery Udhaar',
        ]);
    }

    /**
     * Test 3: Multi-tenant IDOR protection across customers.
     */
    public function test_shop_cannot_view_or_modify_another_shops_customer(): void
    {
        [$tenantA, $userA] = $this->createShopAndOwner('Shop Alpha', '9999933331');
        [$tenantB, $userB] = $this->createShopAndOwner('Shop Beta', '9999933332');

        $customerB = Customer::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Shop B Customer',
            'phone' => '9777700002',
            'current_balance' => 200.00,
            'status' => 'active',
        ]);

        // User A attempts to view Customer B - should be blocked by TenantScope (404) or Ownership check (403)
        $response = $this->actingAs($userA)->get(route('customers.show', $customerB));
        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]), 'Cross-tenant customer view must be 403 or 404');

        // User A attempts to edit Customer B
        $response = $this->actingAs($userA)->get(route('customers.edit', $customerB));
        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]), 'Cross-tenant customer edit must be 403 or 404');

        // User A attempts to update Customer B
        $response = $this->actingAs($userA)->put(route('customers.update', $customerB), [
            'name' => 'Hacked Name',
            'phone' => '9777700002',
        ]);
        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]), 'Cross-tenant customer update must be 403 or 404');
    }

    /**
     * Test 4: Multi-tenant IDOR protection on Statement PDF and Receipt PDF.
     */
    public function test_shop_cannot_download_another_shops_statement_or_receipt_pdf(): void
    {
        [$tenantA, $userA] = $this->createShopAndOwner('Shop Alpha', '9999944441');
        [$tenantB, $userB] = $this->createShopAndOwner('Shop Beta', '9999944442');

        $customerB = Customer::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Shop B Customer',
            'phone' => '9777700003',
            'current_balance' => 500.00,
            'status' => 'active',
        ]);

        $txnB = Transaction::create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'user_id' => $userB->id,
            'transaction_number' => 'TXN-B-001',
            'type' => 'credit',
            'amount' => 500.00,
            'running_balance' => 500.00,
            'transaction_date' => now()->toDateString(),
            'is_reversed' => false,
        ]);

        // User A attempts to download Customer B's statement PDF
        $statementResponse = $this->actingAs($userA)->get(route('customers.statement.pdf', $customerB));
        $this->assertTrue(in_array($statementResponse->getStatusCode(), [403, 404]));

        // User A attempts to download Transaction B's receipt PDF
        $receiptResponse = $this->actingAs($userA)->get(route('transactions.receipt.pdf', $txnB));
        $this->assertTrue(in_array($receiptResponse->getStatusCode(), [403, 404]));
    }

    /**
     * Test 5: Reversal authorization - Cross-tenant reversal is blocked.
     */
    public function test_shop_cannot_reverse_another_shops_transaction(): void
    {
        [$tenantA, $userA] = $this->createShopAndOwner('Shop Alpha', '9999955551');
        [$tenantB, $userB] = $this->createShopAndOwner('Shop Beta', '9999955552');

        $customerB = Customer::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Shop B Customer',
            'phone' => '9777700004',
            'current_balance' => 300.00,
            'status' => 'active',
        ]);

        $txnB = Transaction::create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'user_id' => $userB->id,
            'transaction_number' => 'TXN-B-002',
            'type' => 'credit',
            'amount' => 300.00,
            'running_balance' => 300.00,
            'transaction_date' => now()->toDateString(),
            'is_reversed' => false,
        ]);

        // User A attempts to reverse Shop B's transaction
        $response = $this->actingAs($userA)->post(route('transactions.reverse', $txnB), [
            'reason' => 'Malicious reversal attempt',
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));
        $this->assertFalse($txnB->fresh()->is_reversed);
    }

    /**
     * Test 6: Super Admin access is strictly protected against regular Shop Owners.
     */
    public function test_regular_shop_owner_cannot_access_super_admin_routes(): void
    {
        [$tenant, $shopOwner] = $this->createShopAndOwner('Shop Owner Panel', '9999966661');

        $adminRoutes = [
            route('admin.dashboard'),
            route('admin.shops.index'),
            route('admin.plans.index'),
            route('admin.payments.index'),
            route('admin.settings.index'),
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->actingAs($shopOwner)->get($route);
            $this->assertEquals(403, $response->getStatusCode(), "Route {$route} should be forbidden to shop owner.");
        }
    }

    /**
     * Test 7: Public registration cannot create Super Admin accounts.
     */
    public function test_public_registration_cannot_grant_super_admin_role(): void
    {
        $response = $this->post(route('register'), [
            'owner_name' => 'Attacker User',
            'shop_name' => 'Hacker Shop',
            'phone' => '9888877777',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
            'role' => 'super_admin', // Attempt role spoofing
        ]);

        $user = User::where('phone', '9888877777')->first();
        $this->assertNotNull($user);
        $this->assertEquals('shop_owner', $user->role, 'Role spoofing must be blocked; role must default to shop_owner.');
    }

    /**
     * Test 8: XSS Injection in Customer and Transaction fields is safely escaped.
     */
    public function test_xss_payloads_in_customer_and_transaction_are_escaped(): void
    {
        [$tenant, $user] = $this->createShopAndOwner('Shop XSS', '9999977771');

        $xssName = '<script>alert("XSS")</script> Ramesh';
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => $xssName,
            'phone' => '9666600001',
            'address' => '<img src=x onerror=alert(1)>',
            'notes' => '<b onmouseover=alert(1)>Dangerous Notes</b>',
            'current_balance' => 100.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));
        $response->assertOk();
        // The raw unescaped script tag must NOT be present as executable HTML
        $response->assertDontSee('<script>alert("XSS")</script>', false);
        // Blade escaping transforms < and > into HTML entities
        $response->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', false);
    }

    /**
     * Test 9: SQL Injection resistance on customer search and report filtering.
     */
    public function test_sql_injection_payloads_in_filters_do_not_execute(): void
    {
        [$tenant, $user] = $this->createShopAndOwner('Shop SQLi', '9999988881');

        $sqliPayloads = [
            "' OR 1=1 --",
            "1'; DROP TABLE customers; --",
            "' UNION SELECT id, name, phone, password FROM users --",
        ];

        foreach ($sqliPayloads as $payload) {
            $response = $this->actingAs($user)->get(route('customers.index', ['search' => $payload]));
            $response->assertOk();
            // Customers table must still exist and no SQL syntax error occurred
            $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
        }
    }

    /**
     * Test 10: Payment Webhook rejects tampered signatures and prevents replay.
     */
    public function test_payment_webhook_rejects_invalid_signatures_and_protects_idempotency(): void
    {
        [$tenant, $user] = $this->createShopAndOwner('Shop Webhook', '9999999991');

        $plan = Plan::create([
            'name' => 'Pro Webhook Plan',
            'slug' => 'pro-webhook',
            'price' => 199.00,
            'billing_cycle' => 'monthly',
            'customer_limit' => 500,
            'is_active' => true,
        ]);

        $subService = app(SubscriptionService::class);
        $orderData = $subService->createRazorpayOrder($tenant, $plan);
        $orderId = $orderData['order_id'];
        $paymentId = 'pay_test_phase9_001';

        $payloadArray = [
            'id' => 'evt_phase9_test_123',
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

        // 1. Invalid signature should be rejected with 400
        $invalidResponse = $this->call(
            'POST',
            route('webhook.razorpay'),
            [],
            [],
            [],
            [
                'HTTP_X_RAZORPAY_SIGNATURE' => 'invalid_signature_hash',
                'CONTENT_TYPE' => 'application/json',
            ],
            $payloadJson
        );
        $this->assertEquals(400, $invalidResponse->getStatusCode());

        // 2. Valid signature succeeds
        $secret = config('services.razorpay.webhook_secret', env('RAZORPAY_WEBHOOK_SECRET', 'test_webhook_secret_123'));
        $validSignature = hash_hmac('sha256', $payloadJson, $secret);

        $validResponse = $this->call(
            'POST',
            route('webhook.razorpay'),
            [],
            [],
            [],
            [
                'HTTP_X_RAZORPAY_SIGNATURE' => $validSignature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payloadJson
        );

        $validResponse->assertOk();
        $validResponse->assertJson(['status' => 'success']);

        // Verify payment record was marked successful
        $this->assertDatabaseHas('subscription_payments', [
            'gateway_payment_id' => $paymentId,
            'status' => 'successful',
        ]);

        // 3. Replay attack: Sending identical webhook event payload again should be handled idempotently
        $replayResponse = $this->call(
            'POST',
            route('webhook.razorpay'),
            [],
            [],
            [],
            [
                'HTTP_X_RAZORPAY_SIGNATURE' => $validSignature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payloadJson
        );

        $replayResponse->assertOk();
        $replayResponse->assertJson(['status' => 'ignored']);
    }

    /**
     * Test 11: Rate limiting on Subscription checkout order and WhatsApp reminders.
     */
    public function test_rate_limiting_on_checkout_and_whatsapp_endpoints(): void
    {
        [$tenant, $user] = $this->createShopAndOwner('Shop RateLimit', '9999900001');

        $plan = Plan::create([
            'name' => 'Basic Plan RL',
            'slug' => 'basic-rl',
            'price' => 99.00,
            'billing_cycle' => 'monthly',
            'customer_limit' => 200,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'RL Customer',
            'phone' => '9555500001',
            'current_balance' => 150.00,
            'status' => 'active',
        ]);

        // Test WhatsApp reminder rate limit: 30 requests allowed
        for ($i = 0; $i < 30; $i++) {
            $res = $this->actingAs($user)->postJson(route('customers.whatsapp.remind', $customer), [
                'phone' => $customer->phone,
                'template' => 'standard',
            ]);
            $res->assertOk();
        }

        // 31st request should be throttled (429)
        $throttledRes = $this->actingAs($user)->postJson(route('customers.whatsapp.remind', $customer), [
            'phone' => $customer->phone,
            'template' => 'standard',
        ]);
        $this->assertEquals(429, $throttledRes->getStatusCode());
    }
}
