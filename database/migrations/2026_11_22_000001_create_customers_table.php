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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('phone', 20);
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable()->default(0.00);
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('last_transaction_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'phone']);
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'current_balance']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

