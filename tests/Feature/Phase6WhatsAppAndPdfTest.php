<?php

namespace Tests\Feature;

use App\Helpers\CurrencyHelper;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Services\TransactionService;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase6WhatsAppAndPdfTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected User $user1;
    protected Customer $customer1;

    protected Tenant $tenant2;
    protected User $user2;
    protected Customer $customer2;

    protected TransactionService $transactionService;
    protected WhatsappService $whatsappService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transactionService = app(TransactionService::class);
        $this->whatsappService = app(WhatsappService::class);

        // Shop 1
        $this->tenant1 = Tenant::create([
            'name' => 'Gupta Provision Store',
            'slug' => 'gupta-provision-store',
            'owner_name' => 'Manoj Gupta',
            'phone' => '9811000001',
            'address' => 'Shop 4, Main Bazar, Lucknow',
            'status' => 'active',
        ]);
        $this->user1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Manoj Gupta',
            'phone' => '9811000001',
            'email' => 'manoj@guptastore.com',
            'password' => Hash::make('secret123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
        $this->customer1 = Customer::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Ramesh Chandra',
            'phone' => '9876543210',
            'current_balance' => '0.00',
        ]);

        // Shop 2
        $this->tenant2 = Tenant::create([
            'name' => 'Verma Kirana',
            'slug' => 'verma-kirana',
            'owner_name' => 'Suresh Verma',
            'phone' => '9811000002',
            'status' => 'active',
        ]);
        $this->user2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Suresh Verma',
            'phone' => '9811000002',
            'email' => 'suresh@vermakirana.com',
            'password' => Hash::make('secret123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
        $this->customer2 = Customer::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Vikram Singh',
            'phone' => '9876500000',
            'current_balance' => '0.00',
        ]);
    }

    public function test_whatsapp_service_generates_exact_pre_filled_reminder_message(): void
    {
        // Give customer1 credit of 1250.00
        $this->transactionService->recordTransaction($this->customer1, $this->user1, [
            'type' => 'credit',
            'amount' => '1250.00',
            'transaction_date' => Carbon::today()->toDateString(),
            'description' => 'Grocery items',
        ]);
        $this->customer1->refresh();

        $templates = $this->whatsappService->getReminderTemplates($this->customer1, $this->tenant1);

        $this->assertArrayHasKey('standard', $templates);
        $this->assertArrayHasKey('pure_hindi', $templates);
        $this->assertArrayHasKey('english', $templates);

        $expectedStandardMessage = "Namaste Ramesh Chandra,\n\n"
            . "Aapke account mein ₹1,250.00 ka payment pending hai.\n\n"
            . "Kripya apni suvidha ke anusar payment kar dein.\n\n"
            . "Thank you,\nGupta Provision Store";

        $this->assertEquals($expectedStandardMessage, $templates['standard']['message']);

        // Check phone cleaning and click-to-chat URL
        $url = $this->whatsappService->generateReminderUrl($this->customer1, $this->tenant1, 'standard');
        $this->assertStringStartsWith('https://wa.me/919876543210?text=', $url);
        $this->assertStringContainsString('Namaste%20Ramesh%20Chandra', $url);
    }

    public function test_shop_owner_can_initiate_whatsapp_reminder_and_logs_audit_trail(): void
    {
        // Credit of 2500
        $this->transactionService->recordTransaction($this->customer1, $this->user1, [
            'type' => 'credit',
            'amount' => '2500.00',
            'transaction_date' => Carbon::today()->toDateString(),
        ]);
        $this->customer1->refresh();

        $response = $this->actingAs($this->user1)->post(route('customers.whatsapp.remind', $this->customer1), [
            'phone' => '9876543210',
            'template' => 'standard',
            'custom_message' => 'Custom edited reminder: Kripya kal sham tak payment kar dein.',
        ]);

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://wa.me/919876543210?text=', $response->headers->get('Location'));

        // Assert audit log was recorded in whatsapp_logs
        $this->assertDatabaseHas('whatsapp_logs', [
            'tenant_id' => $this->tenant1->id,
            'customer_id' => $this->customer1->id,
            'phone' => '919876543210',
            'status' => 'initiated',
            'template_type' => 'standard',
            'sent_by_user_id' => $this->user1->id,
        ]);

        $log = WhatsappLog::where('customer_id', $this->customer1->id)->first();
        $this->assertStringContainsString('Custom edited reminder', $log->message);
    }

    public function test_whatsapp_reminder_ajax_returns_json_with_url(): void
    {
        $response = $this->actingAs($this->user1)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('customers.whatsapp.remind', $this->customer1), [
                'phone' => '9876543210',
                'template' => 'english',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'whatsapp_url',
            'log' => ['id', 'phone', 'status', 'created_at'],
        ]);
        $this->assertEquals('success', $response->json('status'));
        $this->assertStringStartsWith('https://wa.me/919876543210?text=', $response->json('whatsapp_url'));
    }

    public function test_customer_pdf_statement_download(): void
    {
        // Record Credit 3000
        $this->transactionService->recordTransaction($this->customer1, $this->user1, [
            'type' => 'credit',
            'amount' => '3000.00',
            'transaction_date' => Carbon::today()->subDays(5)->toDateString(),
            'description' => 'Wheat & Oil',
        ]);

        // Record Payment 1000
        $this->transactionService->recordTransaction($this->customer1, $this->user1, [
            'type' => 'payment',
            'amount' => '1000.00',
            'payment_mode' => 'cash',
            'transaction_date' => Carbon::today()->subDays(2)->toDateString(),
            'description' => 'Cash received',
        ]);

        $response = $this->actingAs($this->user1)->get(route('customers.statement.pdf', $this->customer1));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Statement-RameshChandra', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_payment_receipt_pdf_download_and_currency_in_words(): void
    {
        // 1. Give credit 5000
        $this->transactionService->recordTransaction($this->customer1, $this->user1, [
            'type' => 'credit',
            'amount' => '5000.00',
            'transaction_date' => Carbon::today()->toDateString(),
        ]);

        // 2. Receive payment 2500
        $paymentTxn = $this->transactionService->recordTransaction($this->customer1, $this->user1, [
            'type' => 'payment',
            'amount' => '2500.00',
            'payment_mode' => 'upi',
            'reference_number' => 'UPI-987123654',
            'transaction_date' => Carbon::today()->toDateString(),
            'description' => 'PhonePe UPI receipt',
        ]);

        // Test CurrencyHelper words
        $words = CurrencyHelper::toIndianWords('2500.00');
        $this->assertEquals('Two Thousand Five Hundred Rupees Only', $words);

        $response = $this->actingAs($this->user1)->get(route('transactions.receipt.pdf', $paymentTxn));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Receipt-' . $paymentTxn->transaction_number, (string) $response->headers->get('Content-Disposition'));
    }

    public function test_share_statement_on_whatsapp(): void
    {
        $this->transactionService->recordTransaction($this->customer1, $this->user1, [
            'type' => 'credit',
            'amount' => '1800.00',
            'transaction_date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($this->user1)->get(route('customers.whatsapp.statement', $this->customer1));

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://wa.me/919876543210?text=', $response->headers->get('Location'));

        $this->assertDatabaseHas('whatsapp_logs', [
            'customer_id' => $this->customer1->id,
            'template_type' => 'statement_summary',
            'status' => 'initiated',
        ]);
    }

    public function test_share_payment_receipt_on_whatsapp(): void
    {
        $this->transactionService->recordTransaction($this->customer1, $this->user1, [
            'type' => 'credit',
            'amount' => '2000.00',
            'transaction_date' => Carbon::today()->toDateString(),
        ]);

        $paymentTxn = $this->transactionService->recordTransaction($this->customer1, $this->user1, [
            'type' => 'payment',
            'amount' => '1000.00',
            'payment_mode' => 'cash',
            'transaction_date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($this->user1)->get(route('transactions.whatsapp.receipt', $paymentTxn));

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://wa.me/919876543210?text=', $response->headers->get('Location'));

        $this->assertDatabaseHas('whatsapp_logs', [
            'customer_id' => $this->customer1->id,
            'template_type' => 'payment_receipt',
            'status' => 'initiated',
        ]);
    }

    public function test_cross_tenant_whatsapp_and_pdf_access_forbidden(): void
    {
        // Shop 2 transaction
        $this->transactionService->recordTransaction($this->customer2, $this->user2, [
            'type' => 'credit',
            'amount' => '4000.00',
            'transaction_date' => Carbon::today()->toDateString(),
        ]);
        $shop2Payment = $this->transactionService->recordTransaction($this->customer2, $this->user2, [
            'type' => 'payment',
            'amount' => '2000.00',
            'payment_mode' => 'cash',
            'transaction_date' => Carbon::today()->toDateString(),
        ]);

        // User 1 tries to access Shop 2's customer statement PDF
        $statementResponse = $this->actingAs($this->user1)->get(route('customers.statement.pdf', $this->customer2));
        $this->assertTrue(in_array($statementResponse->status(), [403, 404]));

        // User 1 tries to send WhatsApp reminder to Shop 2 customer
        $remindResponse = $this->actingAs($this->user1)->post(route('customers.whatsapp.remind', $this->customer2), [
            'phone' => '9876500000',
            'template' => 'standard',
        ]);
        $this->assertTrue(in_array($remindResponse->status(), [403, 404]));

        // User 1 tries to download Shop 2 payment receipt PDF
        $receiptResponse = $this->actingAs($this->user1)->get(route('transactions.receipt.pdf', $shop2Payment));
        $this->assertTrue(in_array($receiptResponse->status(), [403, 404]));
    }
}

