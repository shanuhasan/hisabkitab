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
        // 1. Platform Settings Table
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->text('value')->nullable();
            $table->string('group', 30)->default('general');
            $table->timestamps();
        });

        // 2. Admin Activity Logs Table
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action', 100);
            $table->string('target_type', 50)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        // 3. Enhance Subscription Payments with Refund Tracking
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->string('refund_status', 30)->default('none')->after('status'); // none, refunded
            $table->timestamp('refunded_at')->nullable()->after('refund_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn(['refund_status', 'refunded_at']);
        });

        Schema::dropIfExists('admin_activity_logs');
        Schema::dropIfExists('platform_settings');
    }
};

