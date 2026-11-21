<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Tenant;

class WhatsappService
{
    /**
     * Generate pre-composed WhatsApp reminder URL.
     */
    public function generateReminderUrl(Customer $customer, Tenant $shop): string
    {
        // Clean Indian phone number (ensure country code 91)
        $cleanPhone = preg_replace('/[^0-9]/', '', $customer->phone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        $balanceFormatted = number_format((float) $customer->current_balance, 2);

        $message = "Namaste {$customer->name} ji,\n\n"
            . "Aapka {$shop->name} par baki hisab (udhaar) balance ₹{$balanceFormatted} hai.\n"
            . "Kripya jald se jald bhugtan karein.\n\n"
            . "Dhanyawad,\n{$shop->name}\nPhone: {$shop->phone}";

        return 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($message);
    }
}

