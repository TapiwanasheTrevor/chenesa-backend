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
        Schema::create('tank_user', function (Blueprint $table) {
            $table->uuid('tank_id');
            $table->uuid('user_id');
            $table->boolean('can_order_water')->default(true);
            $table->boolean('receive_alerts')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('tank_id')->references('id')->on('tanks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Composite primary key
            $table->primary(['tank_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tank_user');
    }
};
