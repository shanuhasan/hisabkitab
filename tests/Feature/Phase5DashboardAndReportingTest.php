<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase5DashboardAndReportingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected User $user1;
    protected Customer $customer1A;
    protected Customer $customer1B;

    protected Tenant $tenant2;
    protected User $user2;
    protected Customer $customer2;

    protected TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transactionService = app(TransactionService::class);

        // Setup Shop 1
        $this->tenant1 = Tenant::create([
            'name' => 'Sharma Kirana Store',
            'slug' => 'sharma-kirana-store',
            'owner_name' => 'Rajesh Sharma',
            'phone' => '9811000001',
            'address' => 'Shop 12, Chandni Chowk, Delhi',
            'status' => 'active',
        ]);
        $this->user1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Rajesh Sharma',
            'phone' => '9811000001',
            'email' => 'rajesh@sharmakirana.com',
            'password' => Hash::make('secret123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        $this->customer1A = Customer::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Amit Kumar',
            'phone' => '9822000001',
            'current_balance' => '0.00',
        ]);

        $this->customer1B = Customer::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Brijesh Mishra',
            'phone' => '9822000002',
            'current_balance' => '0.00',
        ]);

        // Setup Shop 2
        $this->tenant2 = Tenant::create([
            'name' => 'Verma General Store',
            'slug' => 'verma-general-store',
            'owner_name' => 'Suresh Verma',
            'phone' => '9811000002',
            'status' => 'active',
        ]);
        $this->user2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Suresh Verma',
            'phone' => '9811000002',
            'email' => 'suresh@vermastore.com',
            'password' => Hash::make('secret123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        $this->customer2 = Customer::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Dinesh Gupta',
            'phone' => '9822000099',
            'current_balance' => '0.00',
        ]);
    }

    protected function addCredit(Customer $customer, User $user, string $amount, ?string $date = null, string $desc = 'Credit Entry'): Transaction
    {
        return $this->transactionService->recordTransaction($customer, $user, [
            'type' => 'credit',
            'amount' => $amount,
            'transaction_date' => $date ?: Carbon::today()->toDateString(),
            'description' => $desc,
        ]);
    }

    protected function addPayment(Customer $customer, User $user, string $amount, string $mode = 'cash', ?string $date = null, string $desc = 'Payment Entry', bool $allowAdvance = false): Transaction
    {
        return $this->transactionService->recordTransaction($customer, $user, [
            'type' => 'payment',
            'amount' => $amount,
            'payment_mode' => $mode,
            'transaction_date' => $date ?: Carbon::today()->toDateString(),
            'description' => $desc,
            'allow_advance' => $allowAdvance,
        ]);
    }

    public function test_shop_owner_can_view_dashboard_with_accurate_metrics(): void
    {
        // Customer 1A: Credit Rs. 1500, Payment Rs. 500 => Balance Rs. 1000
        $this->addCredit($this->customer1A, $this->user1, '1500.00', Carbon::today()->toDateString(), 'Dal & Chawal');
        $this->addPayment($this->customer1A, $this->user1, '500.00', 'cash', Carbon::today()->toDateString(), 'Partial payment');

        // Customer 1B: Credit Rs. 2000 => Balance Rs. 2000
        $this->addCredit($this->customer1B, $this->user1, '2000.00', Carbon::today()->toDateString(), 'Atta & Sugar');

        $response = $this->actingAs($this->user1)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('dashboardData');

        $dashboardData = $response->viewData('dashboardData');
        $metrics = $dashboardData['metrics'];
        $this->assertEquals(2, $metrics['total_customers']);
        $this->assertEquals('3500.00', $metrics['total_udhaar_given']);
        $this->assertEquals('500.00', $metrics['total_payments_received']);
        $this->assertEquals('3000.00', $metrics['total_outstanding_balance']);
        $this->assertEquals('3500.00', $metrics['today_udhaar']);
        $this->assertEquals('500.00', $metrics['today_collection']);
    }

    public function test_dashboard_metrics_exclude_reversed_transactions(): void
    {
        // Give Udhaar Rs. 1000
        $creditTxn = $this->addCredit($this->customer1A, $this->user1, '1000.00', Carbon::today()->toDateString(), 'Wrong entry');

        // Give another Udhaar Rs. 400
        $this->addCredit($this->customer1A, $this->user1, '400.00', Carbon::today()->toDateString(), 'Actual grocery item');

        // Reverse the 1000 credit
        $this->transactionService->reverseTransaction($creditTxn, $this->user1, 'Customer returned items immediately');

        $response = $this->actingAs($this->user1)->get(route('dashboard'));
        $dashboardData = $response->viewData('dashboardData');
        $metrics = $dashboardData['metrics'];

        // Total Udhaar given should strictly be 400.00 (the 1000 is excluded because is_reversed = true)
        $this->assertEquals('400.00', $metrics['total_udhaar_given']);
        $this->assertEquals('400.00', $metrics['total_outstanding_balance']);
    }

    public function test_dashboard_displays_recent_transactions_and_top_outstanding(): void
    {
        $this->addCredit($this->customer1A, $this->user1, '800.00', Carbon::today()->toDateString(), 'Ghee & Spices');
        $this->addCredit($this->customer1B, $this->user1, '2500.00', Carbon::today()->toDateString(), 'Rice Bag');

        $response = $this->actingAs($this->user1)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Amit Kumar');
        $response->assertSee('Brijesh Mishra');

        // Highest outstanding should list Brijesh (2500) before Amit (800)
        $dashboardData = $response->viewData('dashboardData');
        $highestOutstanding = $dashboardData['highest_outstanding_customers'];
        $this->assertEquals('Brijesh Mishra', $highestOutstanding->first()->name);
        $this->assertEquals('2500.00', (string) $highestOutstanding->first()->current_balance);
    }

    public function test_period_report_calculates_accurate_credit_and_payment_totals(): void
    {
        // 1. Credit of 1200
        $this->addCredit($this->customer1A, $this->user1, '1200.00', Carbon::today()->toDateString(), 'Monthly ration');

        // 2. Payment of 700 via UPI
        $this->addPayment($this->customer1A, $this->user1, '700.00', 'upi', Carbon::today()->toDateString(), 'GPay received');

        $response = $this->actingAs($this->user1)->get(route('reports.index', [
            'tab' => 'period',
            'from_date' => Carbon::today()->toDateString(),
            'to_date' => Carbon::today()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('periodData');

        $periodData = $response->viewData('periodData');
        $this->assertEquals('1200.00', $periodData['total_credit']);
        $this->assertEquals('700.00', $periodData['total_payment']);
        $this->assertEquals('500.00', $periodData['net_change']);
        $this->assertEquals(2, $periodData['transaction_count']);
    }

    public function test_outstanding_customers_report_lists_debtors(): void
    {
        $this->addCredit($this->customer1A, $this->user1, '500.00');
        $this->addCredit($this->customer1B, $this->user1, '3000.00');

        $response = $this->actingAs($this->user1)->get(route('reports.index', [
            'tab' => 'outstanding',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('outstandingData');

        $data = $response->viewData('outstandingData');
        $this->assertEquals('3500.00', $data['total_outstanding']);
        $this->assertEquals(2, $data['count']);
        $this->assertEquals('Brijesh Mishra', $data['customers']->first()->name);
    }

    public function test_collection_report_calculates_payment_modes_and_total_collected(): void
    {
        $this->addCredit($this->customer1A, $this->user1, '2000.00');
        $this->addPayment($this->customer1A, $this->user1, '600.00', 'cash');
        $this->addPayment($this->customer1A, $this->user1, '400.00', 'upi');

        $response = $this->actingAs($this->user1)->get(route('reports.index', [
            'tab' => 'collection',
            'from_date' => Carbon::today()->toDateString(),
            'to_date' => Carbon::today()->toDateString(),
        ]));

        $response->assertStatus(200);
        $data = $response->viewData('collectionData');

        $this->assertEquals('1000.00', $data['total_collected']);
        $this->assertEquals(2, $data['count']);
    }

    public function test_pdf_export_returns_valid_downloadable_pdf_file(): void
    {
        $this->addCredit($this->customer1A, $this->user1, '1000.00');

        $response = $this->actingAs($this->user1)->get(route('reports.export.pdf', [
            'report' => 'period',
            'from_date' => Carbon::today()->toDateString(),
            'to_date' => Carbon::today()->toDateString(),
        ]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('UdhaarHisab-Report-period', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_csv_export_returns_streamed_csv_with_proper_headers(): void
    {
        $this->addCredit($this->customer1A, $this->user1, '1500.00', null, 'CSV Export test credit');

        $response = $this->actingAs($this->user1)->get(route('reports.export.csv', [
            'report' => 'period',
            'from_date' => Carbon::today()->toDateString(),
            'to_date' => Carbon::today()->toDateString(),
        ]));

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('UdhaarHisab-period', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_cross_tenant_reporting_isolation(): void
    {
        // Shop 1: 1 customer, 1 credit of 1000
        $this->addCredit($this->customer1A, $this->user1, '1000.00', null, 'Shop 1 secret credit');

        // Shop 2: 1 customer, 1 credit of 5000
        $this->addCredit($this->customer2, $this->user2, '5000.00', null, 'Shop 2 secret credit');

        // User 2 logs in and views dashboard
        $response = $this->actingAs($this->user2)->get(route('dashboard'));

        $dashboardData = $response->viewData('dashboardData');
        $metrics = $dashboardData['metrics'];
        // Shop 2 should only see 5000.00, NOT 6000.00 and NOT Shop 1's customer
        $this->assertEquals('5000.00', $metrics['total_udhaar_given']);
        $this->assertEquals('5000.00', $metrics['total_outstanding_balance']);
        $this->assertEquals(1, $metrics['total_customers']);

        $response->assertDontSee('Sharma Kirana Store');
        $response->assertDontSee('Amit Kumar');
        $response->assertSee('Dinesh Gupta');

        // User 2 views period report
        $reportResponse = $this->actingAs($this->user2)->get(route('reports.index', ['tab' => 'period']));
        $periodData = $reportResponse->viewData('periodData');
        $this->assertEquals('5000.00', $periodData['total_credit']);
        $this->assertEquals(1, $periodData['transaction_count']);
    }
}
