<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    protected string $razorpayKey;
    protected string $razorpaySecret;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->razorpayKey = config('services.razorpay.key') ?? env('RAZORPAY_KEY_ID', 'rzp_test_udhaarhisab_key');
        $this->razorpaySecret = config('services.razorpay.secret') ?? env('RAZORPAY_KEY_SECRET', 'test_secret_udhaarhisab_123');
        $this->webhookSecret = config('services.razorpay.webhook_secret') ?? env('RAZORPAY_WEBHOOK_SECRET', 'test_webhook_secret_123');
    }

    /**
     * Get active or current subscription for tenant.
     * If no subscription exists, auto-attaches default Free Plan.
     */
    public function getCurrentSubscription(Tenant $tenant): Subscription
    {
        $subscription = Subscription::with('plan')
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->first();

        if (!$subscription) {
            $trialPlan = Plan::where('slug', 'trial')->first() ?? Plan::firstOrCreate(
                ['slug' => 'trial'],
                [
                    'name' => 'Free 30-Day Trial',
                    'price' => 0.00,
                    'duration_days' => 30,
                    'customer_limit' => 100,
                    'features' => [
                        'pdf_statements' => true,
                        'whatsapp_reminders' => true,
                        'csv_exports' => true,
                        'advanced_reports' => true,
                    ],
                    'description' => 'Full-featured 30-day free trial for new shopkeepers.',
                    'is_active' => true,
                ]
            );

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $trialPlan->id,
                'starts_at' => Carbon::today(),
                'ends_at' => Carbon::today()->addDays(30),
                'trial_ends_at' => Carbon::today()->addDays(30),
                'status' => 'trial',
                'payment_details' => ['type' => 'starter_trial'],
            ]);
        }

        return $subscription;
    }

    /**
     * Get detailed status, quotas, and expiry metadata.
     */
    public function getSubscriptionStatus(Tenant $tenant): array
    {
        $subscription = $this->getCurrentSubscription($tenant);
        $plan = $subscription->plan;

        $customerCount = Customer::where('tenant_id', $tenant->id)->count();
        $customerLimit = $plan->customer_limit;
        $usagePercent = ($customerLimit > 0)
            ? min(100.0, round(($customerCount / $customerLimit) * 100, 1))
            : 0.0;

        $daysRemaining = $subscription->daysRemaining();
        $isValid = $subscription->isValid();
        $isNearExpiry = $isValid && $daysRemaining <= 5 && !$plan->isFree();

        return [
            'subscription' => $subscription,
            'plan' => $plan,
            'status' => $subscription->status,
            'is_valid' => $isValid,
            'is_active' => $subscription->isActive(),
            'is_in_trial' => $subscription->isInTrial(),
            'is_in_grace' => $subscription->isInGracePeriod(),
            'is_expired' => $subscription->isExpired(),
            'days_remaining' => $daysRemaining,
            'is_near_expiry' => $isNearExpiry,
            'customer_count' => $customerCount,
            'customer_limit' => $customerLimit,
            'usage_percent' => $usagePercent,
        ];
    }

    /**
     * Check if tenant is allowed to add a new customer under their current plan limit.
     */
    public function canAddCustomer(Tenant $tenant): bool
    {
        $status = $this->getSubscriptionStatus($tenant);

        if (!$status['is_valid']) {
            return false;
        }

        $limit = $status['customer_limit'];

        // -1 indicates unlimited customers
        if ($limit === -1) {
            return true;
        }

        return $status['customer_count'] < $limit;
    }

    /**
     * Check if tenant plan allows access to a specific premium feature.
     */
    public function canAccessFeature(Tenant $tenant, string $feature): bool
    {
        $status = $this->getSubscriptionStatus($tenant);

        if (!$status['is_valid']) {
            return false;
        }

        return $status['plan']->hasFeature($feature);
    }

    /**
     * Create a Razorpay Order for purchasing/renewing a plan.
     */
    public function createRazorpayOrder(Tenant $tenant, Plan $plan): array
    {
        if ($plan->isFree()) {
            throw new Exception('Free plan does not require checkout order creation.');
        }

        $amountInPaise = (int) round(((float) $plan->price) * 100);
        $orderId = 'order_' . strtoupper(bin2hex(random_bytes(8)));

        // If live Razorpay API credentials are provided and non-dummy, interact with Razorpay
        if (!str_contains($this->razorpayKey, 'rzp_test_udhaarhisab_key') && strlen($this->razorpaySecret) > 15) {
            try {
                $response = Http::withBasicAuth($this->razorpayKey, $this->razorpaySecret)
                    ->post('https://api.razorpay.com/v1/orders', [
                        'amount' => $amountInPaise,
                        'currency' => 'INR',
                        'receipt' => 'SUB-' . $tenant->id . '-' . time(),
                        'notes' => [
                            'tenant_id' => $tenant->id,
                            'plan_id' => $plan->id,
                            'plan_name' => $plan->name,
                        ],
                    ]);

                if ($response->successful()) {
                    $orderId = $response->json('id');
                }
            } catch (Exception $e) {
                Log::warning('Razorpay API call failed, falling back to local order ID: ' . $e->getMessage());
            }
        }

        // Record pending payment in subscription_payments
        SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'currency' => 'INR',
            'payment_method' => 'razorpay',
            'gateway_order_id' => $orderId,
            'status' => 'pending',
            'metadata' => [
                'plan_name' => $plan->name,
                'duration_days' => $plan->duration_days,
            ],
        ]);

        return [
            'order_id' => $orderId,
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'key_id' => $this->razorpayKey,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'plan_price' => $plan->price,
        ];
    }

    /**
     * Server-side signature verification & atomic subscription activation.
     * NEVER trusts frontend without valid HMAC-SHA256 signature verification!
     */
    public function verifyAndActivatePayment(Tenant $tenant, array $data): Subscription
    {
        $orderId = $data['razorpay_order_id'] ?? '';
        $paymentId = $data['razorpay_payment_id'] ?? '';
        $signature = $data['razorpay_signature'] ?? '';
        $planId = $data['plan_id'] ?? null;

        $plan = Plan::findOrFail($planId);

        // Find pending payment record
        $payment = SubscriptionPayment::where('tenant_id', $tenant->id)
            ->where('gateway_order_id', $orderId)
            ->first();

        // Server-side HMAC-SHA256 signature verification
        $generatedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->razorpaySecret);

        $isSignatureValid = hash_equals($generatedSignature, $signature);

        if (!$isSignatureValid) {
            if ($payment) {
                $payment->update([
                    'status' => 'failed',
                    'error_description' => 'Server-side Razorpay signature verification mismatch.',
                ]);
            }

            throw ValidationException::withMessages([
                'signature' => 'Payment verification failed. Invalid signature. Please contact support.',
            ]);
        }

        return DB::transaction(function () use ($tenant, $plan, $payment, $orderId, $paymentId, $signature) {
            // Update or create payment record
            if ($payment) {
                $payment->update([
                    'gateway_payment_id' => $paymentId,
                    'gateway_signature' => $signature,
                    'status' => 'successful',
                ]);
            } else {
                $payment = SubscriptionPayment::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'amount' => $plan->price,
                    'currency' => 'INR',
                    'payment_method' => 'razorpay',
                    'gateway_order_id' => $orderId,
                    'gateway_payment_id' => $paymentId,
                    'gateway_signature' => $signature,
                    'status' => 'successful',
                ]);
            }

            // Activate / Renew subscription
            $currentSub = Subscription::where('tenant_id', $tenant->id)->latest('id')->first();

            $startDate = Carbon::today();
            // If already on the same plan and active, extend from previous end date
            if ($currentSub && $currentSub->plan_id === $plan->id && $currentSub->isActive() && $currentSub->ends_at) {
                $startDate = $currentSub->ends_at->copy();
            }

            $endDate = $startDate->copy()->addDays($plan->duration_days);

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'status' => 'active',
                'payment_details' => [
                    'order_id' => $orderId,
                    'payment_id' => $paymentId,
                    'amount' => $plan->price,
                    'activated_at' => now()->toDateTimeString(),
                ],
            ]);

            $payment->update(['subscription_id' => $subscription->id]);

            return $subscription;
        });
    }

    /**
     * Process Razorpay Webhook with HMAC-SHA256 verification and idempotency protection.
     */
    public function processWebhook(string $payload, string $signature): array
    {
        // 1. Verify Webhook Signature
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        if (!hash_equals($expectedSignature, $signature)) {
            Log::error('Razorpay Webhook signature mismatch.');
            throw new Exception('Invalid webhook signature');
        }

        $event = json_decode($payload, true);
        $eventName = $event['event'] ?? '';
        $eventId = $event['id'] ?? ('wh_' . md5($payload));

        // 2. Idempotency Check: check if event already processed
        $alreadyProcessed = SubscriptionPayment::where('idempotency_key', $eventId)->exists();
        if ($alreadyProcessed) {
            return ['status' => 'ignored', 'message' => 'Event already processed'];
        }

        // 3. Process event
        if (in_array($eventName, ['payment.captured', 'order.paid'])) {
            $paymentEntity = $event['payload']['payment']['entity'] ?? [];
            $orderId = $paymentEntity['order_id'] ?? null;
            $paymentId = $paymentEntity['id'] ?? null;
            $amount = isset($paymentEntity['amount']) ? ((float) $paymentEntity['amount'] / 100) : 0;

            if ($orderId) {
                $payment = SubscriptionPayment::where('gateway_order_id', $orderId)->first();
                if ($payment && $payment->status !== 'successful') {
                    $plan = $payment->plan;
                    $tenant = Tenant::find($payment->tenant_id);

                    if ($tenant && $plan) {
                        $this->activateSubscriptionForTenant($tenant, $plan, $orderId, $paymentId, $eventId, $payment);
                    }
                }
            }
        } elseif ($eventName === 'payment.failed') {
            $paymentEntity = $event['payload']['payment']['entity'] ?? [];
            $orderId = $paymentEntity['order_id'] ?? null;
            $errorDesc = $paymentEntity['error_description'] ?? 'Payment failed on gateway';

            if ($orderId) {
                SubscriptionPayment::where('gateway_order_id', $orderId)->update([
                    'status' => 'failed',
                    'error_description' => $errorDesc,
                    'idempotency_key' => $eventId,
                ]);
            }
        }

        return ['status' => 'success', 'event' => $eventName];
    }

    /**
     * Helper to activate subscription from webhook or manual process.
     */
    protected function activateSubscriptionForTenant(
        Tenant $tenant,
        Plan $plan,
        string $orderId,
        string $paymentId,
        string $idempotencyKey,
        SubscriptionPayment $payment
    ): Subscription {
        return DB::transaction(function () use ($tenant, $plan, $orderId, $paymentId, $idempotencyKey, $payment) {
            $startDate = Carbon::today();
            $endDate = $startDate->copy()->addDays($plan->duration_days);

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'status' => 'active',
                'payment_details' => [
                    'order_id' => $orderId,
                    'payment_id' => $paymentId,
                    'webhook_event' => $idempotencyKey,
                ],
            ]);

            $payment->update([
                'subscription_id' => $subscription->id,
                'gateway_payment_id' => $paymentId,
                'status' => 'successful',
                'idempotency_key' => $idempotencyKey,
            ]);

            return $subscription;
        });
    }

    /**
     * Switch to free plan (downgrade).
     */
    public function switchToFreePlan(Tenant $tenant): Subscription
    {
        $freePlan = Plan::where('slug', 'free')->firstOrFail();

        return Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $freePlan->id,
            'starts_at' => Carbon::today(),
            'ends_at' => Carbon::today()->addDays($freePlan->duration_days),
            'status' => 'active',
            'payment_details' => ['type' => 'downgrade_to_free'],
        ]);
    }
}
