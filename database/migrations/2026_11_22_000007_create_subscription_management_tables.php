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
        // 1. Enhance Plans Table
        Schema::table('plans', function (Blueprint $table) {
            $table->string('slug', 30)->nullable()->after('name');
            $table->text('description')->nullable()->after('features');
        });

        // 2. Enhance Subscriptions Table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('status', 30)->default('active')->change();
            $table->date('trial_ends_at')->nullable()->after('ends_at');
            $table->date('grace_ends_at')->nullable()->after('trial_ends_at');
        });

        // 3. Create Subscription Payments / Transaction History Table
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('INR');
            $table->string('payment_method', 50)->default('razorpay');
            $table->string('gateway_order_id', 100)->nullable();
            $table->string('gateway_payment_id', 100)->nullable();
            $table->string('gateway_signature', 255)->nullable();
            $table->string('status', 30)->default('pending'); // pending, successful, failed
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->text('error_description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('gateway_order_id');
            $table->index('gateway_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'grace_ends_at']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['slug', 'description']);
        });
    }
};

