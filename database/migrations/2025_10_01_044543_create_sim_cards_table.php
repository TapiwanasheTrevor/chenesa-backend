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
        Schema::create('sim_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('iccid')->unique(); // Integrated Circuit Card ID
            $table->string('phone_number')->nullable()->unique();
            $table->string('provider'); // e.g., MTN, Airtel, Vodacom, etc.
            $table->string('network_type')->default('4G'); // 2G, 3G, 4G, 5G
            $table->enum('status', ['active', 'inactive', 'suspended', 'expired'])->default('inactive');
            $table->decimal('balance', 10, 2)->default(0); // Current credit balance
            $table->decimal('data_balance_mb', 10, 2)->nullable(); // Data balance in MB
            $table->date('activation_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('last_recharge_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index('status');
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sim_cards');
    }
};
