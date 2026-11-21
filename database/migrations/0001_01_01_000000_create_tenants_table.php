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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('owner_name', 100);
            $table->string('phone', 20)->index();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->index();
            $table->string('logo_path', 255)->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

