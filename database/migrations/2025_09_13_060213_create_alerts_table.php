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
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('tank_id')->nullable();
            $table->enum('type', ['low_water', 'critical_water', 'sensor_offline', 'refill_reminder', 'maintenance']);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('tank_id')->references('id')->on('tanks')->onDelete('cascade');
            $table->index(['organization_id', 'is_resolved', 'created_at']);
            $table->index(['tank_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
