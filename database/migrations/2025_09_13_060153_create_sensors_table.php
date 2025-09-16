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
        Schema::create('sensors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('device_id', 100)->unique();
            $table->string('imei', 20)->unique()->nullable();
            $table->string('sim_number', 20)->nullable();
            $table->string('model', 50)->default('DF555');
            $table->string('firmware_version', 20)->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->timestamp('last_seen')->nullable();
            $table->integer('battery_level')->nullable();
            $table->integer('signal_strength')->nullable();
            $table->date('installation_date')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_seen']);
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};
