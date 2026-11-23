<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CustomerService;
use App\Services\ShopRegistrationService;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;

/**
 * A ready-to-use demo shop for showing the mobile app (/app-demo).
 *
 *   php artisan db:seed --class=DemoShopSeeder
 *
 * Login: 9000000001 / demo1234
 */
class DemoShopSeeder extends Seeder
{
    public const PHONE = '9000000001';
    public const PASSWORD = 'demo1234';

    public function run(
        ShopRegistrationService $registration,
        CustomerService $customers,
        TransactionService $transactions
    ): void {
        if (User::where('phone', self::PHONE)->exists()) {
            $this->command?->warn('Demo shop already exists — login ' . self::PHONE . ' / ' . self::PASSWORD);

            return;
        }

        $owner = $registration->register([
            'shop_name' => 'Sharma Kirana Store',
            'owner_name' => 'Ramesh Sharma',
            'phone' => self::PHONE,
            'email' => null,
            'password' => self::PASSWORD,
        ]);

        Tenant::whereKey($owner->tenant_id)->update(['address' => 'Shop 12, Main Bazar, Jaipur']);

        // name, phone, opening baki, [ [type, amount, days ago, note, mode] ... ]
        $data = [
            ['Suresh Yadav', '9812300001', 1200, [
                ['credit', 450, 6, 'Atta 10kg, chini', null],
                ['payment', 800, 3, 'Part payment', 'upi'],
                ['credit', 320, 0, 'Dal, chawal', null],
            ]],
            ['Meena Devi', '9812300002', 0, [
                ['credit', 260, 4, 'Doodh, bread', null],
                ['credit', 140, 1, 'Sabun, tel', null],
            ]],
            ['Rajesh Gupta', '9812300003', 2500, [
                ['payment', 1000, 5, 'Cash', 'cash'],
                ['credit', 780, 2, 'Monthly ration', null],
                ['payment', 500, 0, 'PhonePe', 'upi'],
            ]],
            ['Anita Singh', '9812300004', 0, [
                ['credit', 600, 3, 'Biscuit, namkeen', null],
                ['payment', 600, 1, 'Hisab clear', 'cash'],
            ]],
            ['Mohammed Arif', '9812300005', 0, [
                ['payment', 1000, 2, 'Advance jama', 'cash'],
                ['credit', 350, 0, 'Chai patti, chini', null],
            ]],
        ];

        foreach ($data as [$name, $phone, $opening, $entries]) {
            $customer = $customers->createCustomer(array_filter([
                'name' => $name,
                'phone' => $phone,
                'opening_balance' => $opening ?: null,
                'opening_balance_type' => $opening ? 'credit' : null,
            ]), $owner);

            foreach ($entries as [$type, $amount, $daysAgo, $note, $mode]) {
                $transactions->recordTransaction(Customer::find($customer->id), $owner, [
                    'type' => $type,
                    'amount' => $amount,
                    'transaction_date' => now()->subDays($daysAgo)->toDateString(),
                    'description' => $note,
                    'payment_mode' => $mode ?? 'cash',
                    'allow_advance' => true,
                ]);
            }
        }

        $this->command?->info('Demo shop created — login ' . self::PHONE . ' / ' . self::PASSWORD);
    }
}
