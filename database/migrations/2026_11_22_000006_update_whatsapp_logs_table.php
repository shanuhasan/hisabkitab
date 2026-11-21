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
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->string('status', 30)->default('initiated')->change();
            $table->string('template_type', 50)->nullable()->after('message');
            $table->foreignId('sent_by_user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropForeign(['sent_by_user_id']);
            $table->dropColumn(['template_type', 'sent_by_user_id']);
        });
    }
};

