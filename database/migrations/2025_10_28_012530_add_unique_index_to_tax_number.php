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
        // Check if tax_id column exists in customers table before adding unique index
        if (Schema::hasColumn('customers', 'tax_id')) {
            // Clean up duplicate tax_id in customers (keep first, set others to NULL)
            $duplicateTaxIds = DB::table('customers')
                ->select('tax_id', DB::raw('COUNT(*) as count'))
                ->whereNotNull('tax_id')
                ->where('tax_id', '!=', '')
                ->groupBy('tax_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicateTaxIds as $row) {
                $firstCustomerId = DB::table('customers')
                    ->where('tax_id', $row->tax_id)
                    ->orderBy('id')
                    ->value('id');

                DB::table('customers')
                    ->where('tax_id', $row->tax_id)
                    ->where('id', '!=', $firstCustomerId)
                    ->update(['tax_id' => null]);
            }

            // Add unique index to customers.tax_id
            Schema::table('customers', function (Blueprint $table) {
                $table->unique('tax_id', 'customers_tax_id_unique');
            });
        }

        // Check if tax_number column exists in suppliers table before adding unique index
        if (Schema::hasColumn('suppliers', 'tax_number')) {
            // Clean up duplicate tax_number in suppliers
            $duplicateTaxNumbers = DB::table('suppliers')
                ->select('tax_number', DB::raw('COUNT(*) as count'))
                ->whereNotNull('tax_number')
                ->where('tax_number', '!=', '')
                ->groupBy('tax_number')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicateTaxNumbers as $row) {
                $firstSupplierId = DB::table('suppliers')
                    ->where('tax_number', $row->tax_number)
                    ->orderBy('id')
                    ->value('id');

                DB::table('suppliers')
                    ->where('tax_number', $row->tax_number)
                    ->where('id', '!=', $firstSupplierId)
                    ->update(['tax_number' => null]);
            }

            // Add unique index to suppliers.tax_number
            Schema::table('suppliers', function (Blueprint $table) {
                $table->unique('tax_number', 'suppliers_tax_number_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop unique index from customers.tax_id if it exists
        if (Schema::hasColumn('customers', 'tax_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique('customers_tax_id_unique');
            });
        }

        // Drop unique index from suppliers.tax_number if it exists
        if (Schema::hasColumn('suppliers', 'tax_number')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropUnique('suppliers_tax_number_unique');
            });
        }
    }
};
