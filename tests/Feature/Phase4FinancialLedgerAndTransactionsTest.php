<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionReversal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase4FinancialLedgerAndTransactionsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected User $user1;

    protected Tenant $tenant2;
    protected User $user2;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Shop 1
        $this->tenant1 = Tenant::create([
            'name' => 'Kirana Store 1',
            'slug' => 'kirana-store-1',
            'owner_name' => 'Rajesh Sharma',
            'phone' => '9811000001',
            'status' => 'active',
        ]);
        $this->user1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Rajesh Sharma',
            'phone' => '9811000001',
            'email' => 'rajesh@store1.com',
            'password' => Hash::make('secret123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        // Setup Shop 2
        $this->tenant2 = Tenant::create([
            'name' => 'Kirana Store 2',
            'slug' => 'kirana-store-2',
            'owner_name' => 'Suresh Verma',
            'phone' => '9811000002',
            'status' => 'active',
        ]);
        $this->user2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Suresh Verma',
            'phone' => '9811000002',
            'email' => 'suresh@store2.com',
            'password' => Hash::make('secret123'),
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        // Customer in Shop 1 with zero balance
        $this->customer = Customer::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Pankaj Kumar',
            'phone' => '9876500050',
            'current_balance' => 0.00,
        ]);
    }

    /**
     * Test Case 1: Customer starting with zero balance.
     */
    public function test_customer_starts_with_zero_balance(): void
    {
        $this->assertEquals('0.00', $this->customer->current_balance);
        $this->assertCount(0, $this->customer->transactions);
    }

    /**
     * Test Case 2: New credit entry (Udhaar Dena) increases balance accurately.
     */
    public function test_new_credit_entry_increases_balance_accurately(): void
    {
        $this->actingAs($this->user1);

        $response = $this->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'credit',
            'amount' => 1500.50,
            'transaction_date' => now()->toDateString(),
            'reference_number' => 'BILL-101',
            'description' => '5kg Basmati Rice and Oil',
        ]);

        $response->assertRedirect(route('customers.show', $this->customer));

        // Independent calculation: 0.00 + 1500.50 = 1500.50
        $this->assertEquals('1500.50', $this->customer->fresh()->current_balance);

        $txn = Transaction::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($txn);
        $this->assertEquals('credit', $txn->type);
        $this->assertEquals('1500.50', $txn->amount);
        $this->assertEquals('1500.50', $txn->running_balance);
        $this->assertEquals('BILL-101', $txn->reference_number);
    }

    /**
     * Test Case 3: Partial payment decreases balance accurately.
     */
    public function test_partial_payment_decreases_balance(): void
    {
        $this->actingAs($this->user1);

        // First credit: 1500.00
        $this->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'credit',
            'amount' => 1500.00,
            'transaction_date' => now()->toDateString(),
        ]);

        // Partial payment: 500.00 in Cash
        $response = $this->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'payment',
            'amount' => 500.00,
            'payment_mode' => 'cash',
            'transaction_date' => now()->toDateString(),
            'description' => 'Partial cash payment',
        ]);

        $response->assertRedirect(route('customers.show', $this->customer));

        // Independent calculation: 1500.00 - 500.00 = 1000.00
        $this->assertEquals('1000.00', $this->customer->fresh()->current_balance);

        $latestTxn = $this->customer->transactions()->first(); // latest transaction
        $this->assertEquals('payment', $latestTxn->type);
        $this->assertEquals('500.00', $latestTxn->amount);
        $this->assertEquals('1000.00', $latestTxn->running_balance);
    }

    /**
     * Test Case 4: Full payment brings balance to exactly 0.00.
     */
    public function test_full_payment_settles_balance_to_zero(): void
    {
        $this->actingAs($this->user1);

        // Credit: 800.75
        $this->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'credit',
            'amount' => 800.75,
            'transaction_date' => now()->toDateString(),
        ]);

        // Full Payment: 800.75 via UPI
        $this->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'payment',
            'amount' => 800.75,
            'payment_mode' => 'upi',
            'reference_number' => 'UPI-77665544',
            'transaction_date' => now()->toDateString(),
        ]);

        // Independent calculation: 800.75 - 800.75 = 0.00
        $this->assertEquals('0.00', $this->customer->fresh()->current_balance);
    }

    /**
     * Test Case 5: Multiple payments sequentially update running balance.
     */
    public function test_multiple_payments_update_running_balance_accurately(): void
    {
        $this->actingAs($this->user1);

        // Credit 2000.00
        $this->post('/transactions', ['customer_id' => $this->customer->id, 'type' => 'credit', 'amount' => 2000.00, 'transaction_date' => now()->toDateString()]);

        // Payment 1: 600.00 -> balance 1400.00
        $this->post('/transactions', ['customer_id' => $this->customer->id, 'type' => 'payment', 'amount' => 600.00, 'transaction_date' => now()->toDateString()]);
        $this->assertEquals('1400.00', $this->customer->fresh()->current_balance);

        // Payment 2: 400.00 -> balance 1000.00
        $this->post('/transactions', ['customer_id' => $this->customer->id, 'type' => 'payment', 'amount' => 400.00, 'transaction_date' => now()->toDateString()]);
        $this->assertEquals('1000.00', $this->customer->fresh()->current_balance);

        // Payment 3: 1000.00 -> balance 0.00
        $this->post('/transactions', ['customer_id' => $this->customer->id, 'type' => 'payment', 'amount' => 1000.00, 'transaction_date' => now()->toDateString()]);
        $this->assertEquals('0.00', $this->customer->fresh()->current_balance);
    }

    /**
     * Test Case 6: Invalid payment exceeding balance is rejected without explicit advance flag.
     */
    public function test_invalid_payment_exceeding_balance_is_rejected(): void
    {
        $this->actingAs($this->user1);

        // Customer has credit of 500.00
        $this->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'credit',
            'amount' => 500.00,
            'transaction_date' => now()->toDateString(),
        ]);

        // Customer attempts to pay 600.00 without allow_advance flag
        $response = $this->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'payment',
            'amount' => 600.00,
            'payment_mode' => 'cash',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['amount']);

        // Balance must remain unchanged at 500.00
        $this->assertEquals('500.00', $this->customer->fresh()->current_balance);
    }

    /**
     * Test Case 7: Transaction reversal creates offsetting entry and restores customer balance.
     */
    public function test_transaction_reversal_creates_audit_entry_and_restores_balance(): void
    {
        $this->actingAs($this->user1);

        // 1. Credit 1000.00
        $this->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'credit',
            'amount' => 1000.00,
            'transaction_date' => now()->toDateString(),
        ]);
        $this->assertEquals('1000.00', $this->customer->fresh()->current_balance);

        $txn = Transaction::where('customer_id', $this->customer->id)->first();

        // 2. Reverse the credit transaction
        $response = $this->post("/transactions/{$txn->id}/reverse", [
            'reason' => 'Customer returned all items immediately after billing',
        ]);

        $response->assertRedirect(route('customers.show', $this->customer));

        // Original transaction must be marked as reversed
        $this->assertTrue($txn->fresh()->is_reversed);

        // Audit record must exist in transaction_reversals
        $reversalAudit = TransactionReversal::where('original_transaction_id', $txn->id)->first();
        $this->assertNotNull($reversalAudit);
        $this->assertEquals('Customer returned all items immediately after billing', $reversalAudit->reason);

        // Reversal transaction must be created in ledger
        $reversalTxn = Transaction::find($reversalAudit->reversal_transaction_id);
        $this->assertNotNull($reversalTxn);
        $this->assertEquals('reversal', $reversalTxn->type);
        $this->assertEquals('1000.00', $reversalTxn->amount);

        // Customer balance must be restored back to 0.00
        // Independent calculation: 1000.00 - 1000.00 = 0.00
        $this->assertEquals('0.00', $this->customer->fresh()->current_balance);

        // Cannot reverse an already reversed transaction
        $doubleReverseResponse = $this->post("/transactions/{$txn->id}/reverse", [
            'reason' => 'Attempting double reversal',
        ]);
        $doubleReverseResponse->assertStatus(500); // Exception thrown: already reversed
    }

    /**
     * Test Case 8: Cross-tenant transaction access is strictly forbidden.
     */
    public function test_cross_tenant_transaction_access_is_forbidden(): void
    {
        // Create customer and transaction in Shop 2
        $customerOfShop2 = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Shop 2 Customer',
            'phone' => '9876500099',
            'current_balance' => 0.00,
        ]);

        $this->actingAs($this->user2);
        $this->post('/transactions', [
            'customer_id' => $customerOfShop2->id,
            'type' => 'credit',
            'amount' => 3000.00,
            'transaction_date' => now()->toDateString(),
        ]);
        $shop2Txn = Transaction::withoutGlobalScopes()->where('customer_id', $customerOfShop2->id)->first();

        // Switch to User 1 (Shop 1)
        $this->actingAs($this->user1);

        // Attempt to view Shop 2's transaction (Must return 404 / 403)
        $this->get("/transactions/{$shop2Txn->id}")->assertNotFound();

        // Attempt to reverse Shop 2's transaction
        $this->post("/transactions/{$shop2Txn->id}/reverse", [
            'reason' => 'Malicious cross-tenant reversal attempt',
        ])->assertNotFound();

        // Attempt to create transaction for Shop 2's customer
        $createResponse = $this->post('/transactions', [
            'customer_id' => $customerOfShop2->id,
            'type' => 'credit',
            'amount' => 500.00,
            'transaction_date' => now()->toDateString(),
        ]);
        $createResponse->assertSessionHasErrors(['customer_id']);
    }

    /**
     * Test Case 9: Transaction rollback on failure preserves financial invariants.
     */
    public function test_transaction_rollback_preserves_balance_integrity(): void
    {
        $this->actingAs($this->user1);

        // Start with credit 500.00
        $this->post('/transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'credit',
            'amount' => 500.00,
            'transaction_date' => now()->toDateString(),
        ]);
        $this->assertEquals('500.00', $this->customer->fresh()->current_balance);

        // Simulate failed transaction inside DB transaction
        try {
            DB::transaction(function () {
                $locked = Customer::lockForUpdate()->findOrFail($this->customer->id);
                $locked->current_balance = '99999.00';
                $locked->save();

                throw new \RuntimeException('Simulated system failure mid-transaction');
            });
        } catch (\RuntimeException $e) {
            // Expected simulation
        }

        // Database transaction must have rolled back and preserved original 500.00 balance
        $this->assertEquals('500.00', $this->customer->fresh()->current_balance);
    }
}

