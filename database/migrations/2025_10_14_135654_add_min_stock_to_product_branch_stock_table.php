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
        Schema::table('product_branch_stock', function (Blueprint $table) {
            // Add min_stock column if it doesn't exist
            if (!Schema::hasColumn('product_branch_stock', 'min_stock')) {
                $table->integer('min_stock')->default(10)->after('current_stock');
            }
            
            // Add max_stock column for future use
            if (!Schema::hasColumn('product_branch_stock', 'max_stock')) {
                $table->integer('max_stock')->nullable()->after('min_stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_branch_stock', function (Blueprint $table) {
            if (Schema::hasColumn('product_branch_stock', 'min_stock')) {
                $table->dropColumn('min_stock');
            }
            
            if (Schema::hasColumn('product_branch_stock', 'max_stock')) {
                $table->dropColumn('max_stock');
            }
        });
    }
};
