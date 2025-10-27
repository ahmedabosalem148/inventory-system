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
        // SQLite doesn't support ALTER COLUMN for ENUM changes, so we need to recreate tables
        // For issue_vouchers
        DB::statement("
            UPDATE issue_vouchers 
            SET status = 'APPROVED' 
            WHERE status = 'completed'
        ");

        // For return_vouchers
        DB::statement("
            UPDATE return_vouchers 
            SET status = 'APPROVED' 
            WHERE status = 'completed'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            UPDATE issue_vouchers 
            SET status = 'completed' 
            WHERE status = 'APPROVED'
        ");

        DB::statement("
            UPDATE return_vouchers 
            SET status = 'completed' 
            WHERE status = 'APPROVED'
        ");
    }
};
