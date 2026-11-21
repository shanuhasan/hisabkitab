<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed official subscription plans.
     */
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free',
                'name' => 'Free Plan',
                'price' => 0.00,
                'duration_days' => 365,
                'customer_limit' => 25,
                'features' => [
                    'pdf_statements' => false,
                    'whatsapp_reminders' => true,
                    'csv_exports' => false,
                    'advanced_reports' => false,
                    'priority_support' => false,
                ],
                'description' => 'Ideal for small neighborhood shops. Manage up to 25 customers with basic ledger & reports.',
                'is_active' => true,
            ],
            [
                'slug' => 'basic',
                'name' => 'Basic Plan',
                'price' => 99.00,
                'duration_days' => 30,
                'customer_limit' => 200,
                'features' => [
                    'pdf_statements' => true,
                    'whatsapp_reminders' => true,
                    'csv_exports' => false,
                    'advanced_reports' => false,
                    'priority_support' => false,
                ],
                'description' => 'Great for growing local stores. Up to 200 customers, downloadable PDF statements, and WhatsApp reminders.',
                'is_active' => true,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro Plan',
                'price' => 199.00,
                'duration_days' => 30,
                'customer_limit' => -1, // Unlimited
                'features' => [
                    'pdf_statements' => true,
                    'whatsapp_reminders' => true,
                    'csv_exports' => true,
                    'advanced_reports' => true,
                    'priority_support' => true,
                ],
                'description' => 'For retailers and wholesalers. Unlimited customers, CSV exports, advanced reports, and priority support.',
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}

