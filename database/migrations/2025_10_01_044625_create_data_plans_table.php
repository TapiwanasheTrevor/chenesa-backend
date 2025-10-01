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
        Schema::create('data_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "MTN 1GB Monthly", "Airtel 5GB Weekly"
            $table->string('provider'); // MTN, Airtel, Vodacom, etc.
            $table->decimal('data_amount_mb', 10, 2); // Data in MB
            $table->integer('validity_days'); // How many days the plan is valid
            $table->decimal('cost', 10, 2); // Cost of the plan
            $table->string('currency')->default('ZAR'); // Currency
            $table->enum('plan_type', ['data_only', 'voice_and_data', 'sms_and_data', 'bundle'])->default('data_only');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('provider');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_plans');
    }
};
