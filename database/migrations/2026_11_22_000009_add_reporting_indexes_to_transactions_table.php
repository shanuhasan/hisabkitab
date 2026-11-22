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
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_reversed', 'type', 'transaction_date'], 'idx_txns_tenant_rev_type_date');
            $table->index(['tenant_id', 'is_reversed', 'transaction_date'], 'idx_txns_tenant_rev_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_txns_tenant_rev_type_date');
            $table->dropIndex('idx_txns_tenant_rev_date');
        });
    }
};

