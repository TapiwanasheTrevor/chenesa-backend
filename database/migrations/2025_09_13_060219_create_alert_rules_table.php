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
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('tank_id')->nullable();
            $table->string('name', 100)->nullable();
            $table->string('type', 50);
            $table->json('condition');
            $table->json('action');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('tank_id')->references('id')->on('tanks')->onDelete('cascade');
            $table->index(['organization_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
