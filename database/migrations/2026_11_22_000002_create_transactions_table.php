<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('transaction_number', 50);
            $table->string('reference_number', 100)->nullable();
            $table->enum('type', ['credit', 'payment', 'reversal']);
            $table->decimal('amount', 15, 2);
            $table->decimal('running_balance', 15, 2);
            $table->enum('payment_mode', ['cash', 'upi', 'bank_transfer', 'cheque', 'other'])->default('cash');
            $table->date('transaction_date');
            $table->string('description', 255)->nullable();
            $table->string('bill_image_path', 255)->nullable();
            $table->boolean('is_reversed')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'transaction_number']);
            $table->index(['tenant_id', 'customer_id', 'transaction_date']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

