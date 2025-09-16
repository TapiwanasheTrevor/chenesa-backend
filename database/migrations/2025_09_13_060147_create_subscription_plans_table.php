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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('currency', ['USD', 'ZAR', 'ZWL'])->default('USD');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'annually'])->default('monthly');
            $table->integer('max_tanks')->default(1);
            $table->integer('max_users')->default(5);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
