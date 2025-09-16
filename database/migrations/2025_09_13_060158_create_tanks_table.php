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
        Schema::create('tanks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('sensor_id')->nullable();
            $table->string('name', 100);
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('capacity_liters');
            $table->integer('height_mm');
            $table->integer('diameter_mm')->nullable();
            $table->enum('shape', ['cylindrical', 'rectangular', 'custom'])->default('cylindrical');
            $table->string('material', 50)->nullable();
            $table->integer('installation_height_mm')->default(0);
            $table->integer('low_level_threshold')->default(20);
            $table->integer('critical_level_threshold')->default(10);
            $table->boolean('refill_enabled')->default(true);
            $table->integer('auto_refill_threshold')->default(30);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('sensor_id')->references('id')->on('sensors')->onDelete('set null');
            $table->index(['organization_id', 'sensor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanks');
    }
};
