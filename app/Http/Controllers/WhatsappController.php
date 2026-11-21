<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use App\Services\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    public function __construct(protected WhatsappService $whatsappService) {}

    /**
     * Prepare, log, and initiate a WhatsApp payment reminder.
     */
    public function sendReminder(Request $request, Customer $customer): JsonResponse|RedirectResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to customer.');
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'template' => ['nullable', 'string', 'in:standard,english,pure_hindi,formal_english,custom'],
            'custom_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenant = auth()->user()->tenant;
        $templateType = $validated['template'] ?? 'standard';

        // Select template message or custom edited text
        if ($templateType === 'custom' && !empty($validated['custom_message'])) {
            $message = trim($validated['custom_message']);
        } else {
            $message = !empty($validated['custom_message'])
                ? trim($validated['custom_message'])
                : $this->whatsappService->buildReminderMessage($customer, $tenant, $templateType);
        }

        $phone = $validated['phone'];

        // Record reminder log with status 'initiated'
        $log = $this->whatsappService->logReminder(
            customer: $customer,
            tenant: $tenant,
            phone: $phone,
            message: $message,
            templateType: $templateType,
            status: 'initiated',
            userId: auth()->id()
        );

        $whatsappUrl = $this->whatsappService->buildClickToChatUrl($phone, $message);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'WhatsApp reminder initiated successfully.',
                'whatsapp_url' => $whatsappUrl,
                'log' => [
                    'id' => $log->id,
                    'phone' => $log->phone,
                    'status' => $log->status,
                    'created_at' => $log->created_at->format('d M Y, h:i A'),
                ],
            ]);
        }

        return redirect()->away($whatsappUrl);
    }

    /**
     * Share payment receipt via WhatsApp click-to-chat.
     */
    public function shareReceipt(Transaction $transaction): RedirectResponse
    {
        if ($transaction->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to transaction.');
        }

        $customer = $transaction->customer;
        $tenant = auth()->user()->tenant;
        $message = $this->whatsappService->generateReceiptShareMessage($transaction, $tenant);
        $phone = $customer ? $customer->phone : ($tenant->phone ?? '');

        // Log sharing event
        if ($customer) {
            $this->whatsappService->logReminder(
                customer: $customer,
                tenant: $tenant,
                phone: $phone,
                message: $message,
                templateType: 'payment_receipt',
                status: 'initiated',
                userId: auth()->id()
            );
        }

        $whatsappUrl = $this->whatsappService->buildClickToChatUrl($phone, $message);

        return redirect()->away($whatsappUrl);
    }

    /**
     * Share customer account statement summary via WhatsApp.
     */
    public function shareStatement(Request $request, Customer $customer): RedirectResponse
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized access to customer.');
        }

        $tenant = auth()->user()->tenant;
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $message = $this->whatsappService->generateStatementShareMessage(
            customer: $customer,
            shop: $tenant,
            fromDate: $fromDate,
            toDate: $toDate,
            totalCredit: $customer->total_credit,
            totalPayment: $customer->total_payment,
            balance: (string) $customer->current_balance
        );

        $this->whatsappService->logReminder(
            customer: $customer,
            tenant: $tenant,
            phone: $customer->phone,
            message: $message,
            templateType: 'statement_summary',
            status: 'initiated',
            userId: auth()->id()
        );

        $whatsappUrl = $this->whatsappService->buildClickToChatUrl($customer->phone, $message);

        return redirect()->away($whatsappUrl);
    }
}

