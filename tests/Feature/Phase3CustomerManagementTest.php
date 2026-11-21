<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase3CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected User $user1;

    protected Tenant $tenant2;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Shop 1
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

        // Shop 2
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
    }

    /**
     * Test adding customer without opening balance.
     */
    public function test_can_add_customer_without_opening_balance(): void
    {
        $this->actingAs($this->user1);

        $response = $this->post('/customers', [
            'name' => 'Mohan Lal',
            'phone' => '9876500001',
            'email' => 'mohan@example.com',
            'address' => 'Gali No. 4, Main Bazar',
            'credit_limit' => 5000.00,
            'notes' => 'Regular customer, tea stall owner',
        ]);

        $customer = Customer::where('phone', '9876500001')->first();
        $this->assertNotNull($customer);
        $response->assertRedirect(route('customers.show', $customer));

        $this->assertEquals($this->tenant1->id, $customer->tenant_id);
        $this->assertEquals('Mohan Lal', $customer->name);
        $this->assertEquals('0.00', $customer->current_balance);
        $this->assertCount(0, $customer->transactions);
    }

    /**
     * Test adding customer with opening balance creates audited ledger entry.
     */
    public function test_can_add_customer_with_opening_balance_credit(): void
    {
        $this->actingAs($this->user1);

        $response = $this->post('/customers', [
            'name' => 'Kishore Kumar',
            'phone' => '9876500002',
            'opening_balance' => 1250.50,
            'opening_balance_type' => 'credit',
        ]);

        $customer = Customer::where('phone', '9876500002')->first();
        $this->assertNotNull($customer);
        $response->assertRedirect(route('customers.show', $customer));

        // Balance must be positive credit
        $this->assertEquals('1250.50', $customer->current_balance);

        // An audited opening transaction must exist in transactions table
        $txn = Transaction::where('customer_id', $customer->id)->first();
        $this->assertNotNull($txn);
        $this->assertEquals('credit', $txn->type);
        $this->assertEquals('1250.50', $txn->amount);
        $this->assertEquals('1250.50', $txn->running_balance);
        $this->assertStringContainsString('Opening Balance', $txn->description);
    }

    /**
     * Test adding customer with opening advance (peshi jama).
     */
    public function test_can_add_customer_with_opening_balance_advance(): void
    {
        $this->actingAs($this->user1);

        $response = $this->post('/customers', [
            'name' => 'Pooja Rani',
            'phone' => '9876500003',
            'opening_balance' => 500.00,
            'opening_balance_type' => 'payment',
        ]);

        $customer = Customer::where('phone', '9876500003')->first();
        $this->assertNotNull($customer);

        // Advance means negative balance (shop owes customer)
        $this->assertEquals('-500.00', $customer->current_balance);

        $txn = Transaction::where('customer_id', $customer->id)->first();
        $this->assertNotNull($txn);
        $this->assertEquals('payment', $txn->type);
        $this->assertEquals('500.00', $txn->amount);
        $this->assertEquals('-500.00', $txn->running_balance);
    }

    /**
     * Test duplicate phone number validation within the SAME shop.
     */
    public function test_cannot_add_duplicate_phone_within_same_shop(): void
    {
        $this->actingAs($this->user1);

        // Add first customer
        Customer::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Customer A',
            'phone' => '9876500010',
            'current_balance' => 0.00,
        ]);

        // Attempt to add second customer with same phone in Shop 1
        $response = $this->post('/customers', [
            'name' => 'Customer B',
            'phone' => '9876500010',
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    /**
     * Multi-Tenancy test: Different shops CAN have customers with the same phone.
     */
    public function test_different_shops_can_have_customer_with_same_phone(): void
    {
        // Add customer with phone X in Shop 1
        $this->actingAs($this->user1);
        $this->post('/customers', [
            'name' => 'Sunil (Shop 1)',
            'phone' => '9876500020',
        ])->assertSessionHasNoErrors();

        // Add customer with same phone X in Shop 2
        $this->actingAs($this->user2);
        $this->post('/customers', [
            'name' => 'Sunil (Shop 2)',
            'phone' => '9876500020',
        ])->assertSessionHasNoErrors();

        // Query without global scope to verify database persistence across both tenants
        $this->assertCount(1, Customer::withoutGlobalScopes()->where('tenant_id', $this->tenant1->id)->where('phone', '9876500020')->get());
        $this->assertCount(1, Customer::withoutGlobalScopes()->where('tenant_id', $this->tenant2->id)->where('phone', '9876500020')->get());
    }

    /**
     * Test searching and filtering customers.
     */
    public function test_can_search_and_filter_customers(): void
    {
        $this->actingAs($this->user1);

        Customer::create(['tenant_id' => $this->tenant1->id, 'name' => 'Deepak Verma', 'phone' => '9876500031', 'current_balance' => 1000.00]);
        Customer::create(['tenant_id' => $this->tenant1->id, 'name' => 'Alok Gupta', 'phone' => '9876500032', 'current_balance' => -200.00]);
        Customer::create(['tenant_id' => $this->tenant1->id, 'name' => 'Rohit Mehra', 'phone' => '9876500033', 'current_balance' => 0.00]);

        // Search by name
        $searchResponse = $this->get('/customers?search=Deepak');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Deepak Verma');
        $searchResponse->assertDontSee('Alok Gupta');

        // Filter by receivable (Udhaar > 0)
        $receivableResponse = $this->get('/customers?balance=receivable');
        $receivableResponse->assertStatus(200);
        $receivableResponse->assertSee('Deepak Verma');
        $receivableResponse->assertDontSee('Alok Gupta');

        // Filter by advance (Advance < 0)
        $advanceResponse = $this->get('/customers?balance=advance');
        $advanceResponse->assertStatus(200);
        $advanceResponse->assertSee('Alok Gupta');
        $advanceResponse->assertDontSee('Deepak Verma');
    }

    /**
     * Cross-tenant security: Shop 1 cannot view or modify Shop 2's customer.
     */
    public function test_cross_tenant_customer_access_is_forbidden(): void
    {
        $customerOfShop2 = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Confidential Customer',
            'phone' => '9876500099',
            'current_balance' => 50000.00,
        ]);

        // Act as User 1 (Shop 1)
        $this->actingAs($this->user1);

        // Attempt to view (Returns 404/403 due to TenantScope preventing cross-tenant existence leakage)
        $this->get("/customers/{$customerOfShop2->id}")->assertNotFound();

        // Attempt to edit
        $this->get("/customers/{$customerOfShop2->id}/edit")->assertNotFound();

        // Attempt to update
        $this->put("/customers/{$customerOfShop2->id}", [
            'name' => 'Hacked Name',
            'phone' => '9876500099',
        ])->assertNotFound();

        // Attempt to archive
        $this->delete("/customers/{$customerOfShop2->id}/archive")->assertNotFound();

        // Ensure customer was not tampered with
        $this->assertEquals('Confidential Customer', $customerOfShop2->fresh()->name);
    }

    /**
     * Test archiving and restoring customer.
     */
    public function test_can_archive_and_restore_customer(): void
    {
        $this->actingAs($this->user1);

        $customer = Customer::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Temporary Grahak',
            'phone' => '9876500055',
            'current_balance' => 0.00,
            'status' => 'active',
        ]);

        // Archive
        $archiveResponse = $this->delete("/customers/{$customer->id}/archive");
        $archiveResponse->assertRedirect('/customers');
        $this->assertTrue($customer->fresh()->trashed());
        $this->assertEquals('inactive', $customer->fresh()->status);

        // Verify customer is in the archived tab
        $archivedList = $this->get('/customers?status=archived');
        $archivedList->assertSee('Temporary Grahak');

        // Restore
        $restoreResponse = $this->post("/customers/{$customer->id}/restore");
        $restoreResponse->assertRedirect(route('customers.show', $customer));
        $this->assertFalse($customer->fresh()->trashed());
        $this->assertEquals('active', $customer->fresh()->status);
    }
}
