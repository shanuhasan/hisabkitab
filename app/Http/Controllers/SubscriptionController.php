<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    /**
     * Display Subscription details, current plan, pricing tiers, and payment history.
     */
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        $status = $this->subscriptionService->getSubscriptionStatus($tenant);
        $plans = Plan::where('is_active', true)->orderBy('price', 'asc')->get();

        $payments = SubscriptionPayment::where('tenant_id', $tenant->id)
            ->with('plan')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        return view('subscription.index', compact('tenant', 'status', 'plans', 'payments'));
    }

    /**
     * Create Razorpay Checkout Order.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $tenant = auth()->user()->tenant;
        $plan = Plan::findOrFail($validated['plan_id']);

        try {
            $orderData = $this->subscriptionService->createRazorpayOrder($tenant, $plan);
            return response()->json([
                'status' => 'success',
                'order' => $orderData,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verify Razorpay Payment Signature & Activate Subscription Server-side.
     */
    public function verifyPayment(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $tenant = auth()->user()->tenant;

        try {
            $subscription = $this->subscriptionService->verifyAndActivatePayment($tenant, $validated);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Badhai ho! Aapka {$subscription->plan->name} safaltapoorvak activate ho gaya hai.",
                    'redirect_url' => route('subscription.index'),
                ]);
            }

            return redirect()->route('subscription.index')
                ->with('success', "Badhai ho! Aapka {$subscription->plan->name} safaltapoorvak activate ho gaya hai.");
        } catch (Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('subscription.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Downgrade / Switch to Free Plan.
     */
    public function switchToFree(): RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        $this->subscriptionService->switchToFreePlan($tenant);

        return redirect()->route('subscription.index')
            ->with('info', 'Aapka account Free Plan par switch kar diya gaya hai.');
    }

    /**
     * Razorpay Webhook Handler (CSRF-exempt).
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');

        try {
            $result = $this->subscriptionService->processWebhook($payload, $signature);
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

