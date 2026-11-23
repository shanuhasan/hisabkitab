<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ReminderController extends Controller
{
    public function __construct(protected WhatsappService $whatsappService) {}

    /**
     * GET /api/v1/customers/{customer}/reminder — ready-made WhatsApp messages to choose from.
     */
    public function templates(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);
        $tenant = $request->user()->tenant;

        $templates = collect($this->whatsappService->getReminderTemplates($customer, $tenant))
            ->map(fn ($t, $key) => [
                'key' => $key,
                'name' => $t['name'],
                'language' => $t['language'],
                'message' => $t['message'],
            ])->values();

        return response()->json([
            'customer' => ['id' => $customer->id, 'name' => $customer->name, 'phone' => $customer->phone],
            'templates' => $templates,
        ]);
    }

    /**
     * POST /api/v1/customers/{customer}/reminder — log the reminder and get a wa.me link to open.
     */
    public function send(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        $throttleKey = 'whatsapp-remind:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            return response()->json(['message' => 'Too many reminder requests sent. Please wait a moment.'], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        $validated = $request->validate([
            'template' => ['nullable', 'string', 'in:standard,english,pure_hindi,formal_english,custom'],
            'custom_message' => ['nullable', 'required_if:template,custom', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $tenant = $request->user()->tenant;
        $template = $validated['template'] ?? 'standard';
        $phone = $validated['phone'] ?? $customer->phone;
        $message = !empty($validated['custom_message'])
            ? trim($validated['custom_message'])
            : $this->whatsappService->buildReminderMessage($customer, $tenant, $template);

        $this->whatsappService->logReminder(
            customer: $customer,
            tenant: $tenant,
            phone: $phone,
            message: $message,
            templateType: $template,
            status: 'initiated',
            userId: $request->user()->id
        );

        return response()->json([
            'message' => 'Reminder ready. Open the link to send it on WhatsApp.',
            'whatsapp_url' => $this->whatsappService->buildClickToChatUrl($phone, $message),
            'text' => $message,
        ]);
    }

    protected function authorizeCustomer(Customer $customer): void
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(404, 'Customer not found.');
        }
    }
}
