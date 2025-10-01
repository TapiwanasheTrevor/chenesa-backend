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
        Schema::create('recharge_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sim_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('data_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete(); // Who performed the recharge
            $table->enum('recharge_type', ['airtime', 'data', 'bundle'])->default('airtime');
            $table->decimal('amount', 10, 2); // Amount recharged
            $table->string('currency')->default('ZAR');
            $table->decimal('data_amount_mb', 10, 2)->nullable(); // Data added in MB
            $table->string('reference_number')->nullable()->unique(); // Transaction reference
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->date('recharge_date');
            $table->date('expiry_date')->nullable(); // When this recharge expires
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('sim_card_id');
            $table->index('user_id');
            $table->index('recharge_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recharge_history');
    }
};
