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
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sensor_id');
            $table->uuid('tank_id');
            $table->integer('distance_mm');
            $table->integer('water_level_mm')->nullable();
            $table->decimal('water_level_percentage', 5, 2)->nullable();
            $table->decimal('volume_liters', 10, 2)->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('battery_voltage', 4, 2)->nullable();
            $table->integer('signal_rssi')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->foreign('sensor_id')->references('id')->on('sensors')->onDelete('cascade');
            $table->foreign('tank_id')->references('id')->on('tanks')->onDelete('cascade');
            $table->index(['sensor_id', 'created_at']);
            $table->index(['tank_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
