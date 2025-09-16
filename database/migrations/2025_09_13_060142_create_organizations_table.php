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
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['residential', 'commercial', 'industrial'])->default('residential');
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->enum('country', ['zimbabwe', 'south_africa']);
            $table->string('contact_email');
            $table->string('contact_phone', 50)->nullable();
            $table->enum('subscription_status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->uuid('subscription_plan_id')->nullable();
            $table->timestamps();

            $table->index(['country', 'subscription_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
