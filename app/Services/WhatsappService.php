<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\WhatsappLog;

class WhatsappService
{
    /**
     * Clean and format phone number with country code (defaults to India 91).
     */
    public function cleanPhoneNumber(string $phone, string $countryCode = '91'): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // If 10 digits (Standard Indian mobile), prepend country code
        if (strlen($digits) === 10) {
            return $countryCode . $digits;
        }

        // If 11 digits starting with 0, replace 0 with country code
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return $countryCode . substr($digits, 1);
        }

        // If already 12 digits starting with country code, return as-is
        if (strlen($digits) === 12 && str_starts_with($digits, $countryCode)) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Get available pre-filled reminder templates for a customer.
     */
    public function getReminderTemplates(Customer $customer, Tenant $shop): array
    {
        $amount = number_format(max(0, (float) $customer->current_balance), 2);
        $customerName = $customer->name;
        $shopName = $shop->name;
        $shopPhone = $shop->phone ?? '';

        return [
            'standard' => [
                'name' => 'Standard Hinglish (Default)',
                'language' => 'Hinglish',
                'message' => "Namaste {$customerName},\n\nAapke account mein ₹{$amount} ka payment pending hai.\n\nKripya apni suvidha ke anusar payment kar dein.\n\nThank you,\n{$shopName}",
            ],
            'english' => [
                'name' => 'English (Polite Reminder)',
                'language' => 'English',
                'message' => "Dear {$customerName},\n\nA payment of ₹{$amount} is pending in your account at {$shopName}.\n\nKindly make the payment at your earliest convenience.\n\nThank you,\n{$shopName}",
            ],
            'pure_hindi' => [
                'name' => 'Hindi (हिंदी)',
                'language' => 'Hindi',
                'message' => "नमस्ते {$customerName},\n\nआपके खाते में ₹{$amount} का भुगतान बाकी है।\n\nकृपया अपनी सुविधानुसार भुगतान करें।\n\nधन्यवाद,\n{$shopName}",
            ],
            'formal_english' => [
                'name' => 'Formal English (Business)',
                'language' => 'English',
                'message' => "Dear {$customerName},\n\nThis is a friendly payment reminder from {$shopName}. Your outstanding ledger balance is ₹{$amount}. Please clear the pending dues.\n\nRegards,\n{$shopName}" . ($shopPhone ? "\nPhone: {$shopPhone}" : ""),
            ],
        ];
    }

    /**
     * Generate pre-filled WhatsApp reminder message using selected template.
     */
    public function buildReminderMessage(Customer $customer, Tenant $shop, string $template = 'standard'): string
    {
        $templates = $this->getReminderTemplates($customer, $shop);
        return $templates[$template]['message'] ?? $templates['standard']['message'];
    }

    /**
     * Generate WhatsApp click-to-chat URL.
     */
    public function buildClickToChatUrl(string $phone, string $message): string
    {
        $cleanPhone = $this->cleanPhoneNumber($phone);
        return 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($message);
    }

    /**
     * Convenience method to generate click-to-chat URL for customer reminder.
     */
    public function generateReminderUrl(Customer $customer, Tenant $shop, string $template = 'standard'): string
    {
        $message = $this->buildReminderMessage($customer, $shop, $template);
        return $this->buildClickToChatUrl($customer->phone, $message);
    }

    /**
     * Record initiated/prepared reminder log in database.
     * Architectural readiness: allows future WhatsApp Business Cloud API webhooks to update status.
     */
    public function logReminder(
        Customer $customer,
        Tenant $tenant,
        string $phone,
        string $message,
        string $templateType = 'standard',
        string $status = 'initiated',
        ?int $userId = null
    ): WhatsappLog {
        return WhatsappLog::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'phone' => $this->cleanPhoneNumber($phone),
            'message' => $message,
            'template_type' => $templateType,
            'status' => $status,
            'sent_by_user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * Generate pre-filled message for sharing customer ledger statement.
     */
    public function generateStatementShareMessage(
        Customer $customer,
        Tenant $shop,
        ?string $fromDate = null,
        ?string $toDate = null,
        string $totalCredit = '0.00',
        string $totalPayment = '0.00',
        string $balance = '0.00'
    ): string {
        $periodText = ($fromDate && $toDate)
            ? date('d M Y', strtotime($fromDate)) . ' to ' . date('d M Y', strtotime($toDate))
            : 'All-Time';

        return "Namaste {$customer->name},\n\n"
            . "Here is your account statement summary with {$shop->name} ({$periodText}):\n"
            . "• Total Udhaar: ₹" . number_format((float) $totalCredit, 2) . "\n"
            . "• Total Payments: ₹" . number_format((float) $totalPayment, 2) . "\n"
            . "• Current Balance: ₹" . number_format((float) $balance, 2) . " " . ((float)$balance > 0 ? '(Pending Due)' : '(Advance/Settled)') . "\n\n"
            . "Thank you,\n{$shop->name}\nPhone: " . ($shop->phone ?? '');
    }

    /**
     * Generate pre-filled message for sharing payment receipt.
     */
    public function generateReceiptShareMessage(Transaction $transaction, Tenant $shop): string
    {
        $customer = $transaction->customer;
        $customerName = $customer ? $customer->name : 'Customer';
        $amount = number_format((float) $transaction->amount, 2);
        $date = $transaction->transaction_date->format('d M Y');
        $mode = ucfirst(str_replace('_', ' ', $transaction->payment_mode ?? 'cash'));
        $balance = number_format((float) $transaction->running_balance, 2);

        return "Namaste {$customerName},\n\n"
            . "Payment received with thanks!\n"
            . "• Amount: ₹{$amount}\n"
            . "• Mode: {$mode}\n"
            . "• Receipt No: {$transaction->transaction_number}\n"
            . "• Date: {$date}\n"
            . "• Remaining Balance: ₹{$balance}\n\n"
            . "Thank you,\n{$shop->name}\nPhone: " . ($shop->phone ?? '');
    }
}
