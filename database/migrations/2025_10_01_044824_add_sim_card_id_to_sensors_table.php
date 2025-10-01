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
        Schema::table('sensors', function (Blueprint $table) {
            $table->foreignId('sim_card_id')->nullable()->after('tank_id')->constrained()->nullOnDelete();
            $table->index('sim_card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropForeign(['sim_card_id']);
            $table->dropColumn('sim_card_id');
        });
    }
};
