<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to handle the type change properly in PostgreSQL
        DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE uuid USING tokenable_id::text::uuid');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Use raw SQL to revert the type change
        DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE bigint USING tokenable_id::text::bigint');
    }
};
