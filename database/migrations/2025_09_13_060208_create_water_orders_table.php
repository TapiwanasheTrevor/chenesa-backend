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
        Schema::create('water_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('tank_id');
            $table->uuid('user_id');
            $table->string('order_number', 20)->unique();
            $table->integer('volume_liters');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'])->default('pending');
            $table->date('delivery_date')->nullable();
            $table->string('delivery_time_slot', 50)->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('driver_id')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('tank_id')->references('id')->on('tanks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['organization_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_orders');
    }
};
