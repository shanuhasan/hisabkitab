<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\SubscriptionPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentManagementController extends Controller
{
    /**
     * Display all SaaS subscription payments with filtering and search.
     */
    public function index(Request $request): View
    {
        $status = $request->input('status', 'all');
        $refundStatus = $request->input('refund_status', 'all');
        $search = trim($request->input('search', ''));
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = SubscriptionPayment::with(['tenant', 'plan']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('gateway_order_id', 'like', "%{$search}%")
                  ->orWhere('gateway_payment_id', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($status !== 'all' && in_array($status, ['successful', 'pending', 'failed'])) {
            $query->where('status', $status);
        }

        if ($refundStatus !== 'all' && in_array($refundStatus, ['none', 'refunded'])) {
            $query->where('refund_status', $refundStatus);
        }

        if (!empty($fromDate)) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $totalCollected = (clone $query)->where('status', 'successful')->sum('amount');
        $payments = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('admin.payments.index', compact('payments', 'status', 'refundStatus', 'search', 'fromDate', 'toDate', 'totalCollected'));
    }

    /**
     * Process / Mark a subscription payment as refunded.
     */
    public function refund(Request $request, SubscriptionPayment $payment): RedirectResponse
    {
        if ($payment->status !== 'successful') {
            return redirect()->back()
                ->with('error', 'Only successful payments can be marked as refunded.');
        }

        if ($payment->refund_status === 'refunded') {
            return redirect()->back()
                ->with('warning', 'This transaction has already been refunded.');
        }

        $reason = $request->input('refund_reason', 'Refund approved by Super Admin');

        $payment->update([
            'refund_status' => 'refunded',
            'refunded_at' => now(),
            'error_description' => 'Refunded: ' . $reason,
        ]);

        AdminActivityLog::log(
            action: 'payment_refunded',
            description: "Payment ID: {$payment->gateway_payment_id} (₹{$payment->amount}) marked as refunded. Reason: {$reason}",
            targetType: 'SubscriptionPayment',
            targetId: $payment->id
        );

        return redirect()->back()
            ->with('success', "Payment {$payment->gateway_payment_id} has been successfully marked as refunded.");
    }
}

