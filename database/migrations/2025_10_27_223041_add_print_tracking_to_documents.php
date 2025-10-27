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
        // Add print tracking to issue_vouchers
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->integer('print_count')->default(0)->after('status');
            $table->timestamp('last_printed_at')->nullable()->after('print_count');
            $table->integer('last_printed_by')->nullable()->after('last_printed_at');
        });
        
        // Add print tracking to return_vouchers
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->integer('print_count')->default(0)->after('status');
            $table->timestamp('last_printed_at')->nullable()->after('print_count');
            $table->integer('last_printed_by')->nullable()->after('last_printed_at');
        });
        
        // Add print tracking to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->integer('print_count')->default(0)->after('status');
            $table->timestamp('last_printed_at')->nullable()->after('print_count');
            $table->integer('last_printed_by')->nullable()->after('last_printed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->dropColumn(['print_count', 'last_printed_at', 'last_printed_by']);
        });
        
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->dropColumn(['print_count', 'last_printed_at', 'last_printed_by']);
        });
        
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['print_count', 'last_printed_at', 'last_printed_by']);
        });
    }
};
