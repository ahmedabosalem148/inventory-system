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
        // Check if name column exists and name_ar doesn't
        if (Schema::hasColumn('products', 'name') && !Schema::hasColumn('products', 'name_ar')) {
            // Rename name to name_ar
            Schema::table('products', function (Blueprint $table) {
                $table->renameColumn('name', 'name_ar');
            });
        }
        
        // Check if units_per_carton exists and carton_size doesn't
        if (Schema::hasColumn('products', 'units_per_carton') && !Schema::hasColumn('products', 'carton_size')) {
            // Rename units_per_carton to carton_size
            Schema::table('products', function (Blueprint $table) {
                $table->renameColumn('units_per_carton', 'carton_size');
            });
        }
        
        // Ensure the columns exist with correct types
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'name_ar')) {
                $table->string('name_ar', 190)->after('id');
            }
            if (!Schema::hasColumn('products', 'carton_size')) {
                $table->integer('carton_size')->unsigned()->after('name_ar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'name_ar')) {
                $table->renameColumn('name_ar', 'name');
            }
            if (Schema::hasColumn('products', 'carton_size')) {
                $table->renameColumn('carton_size', 'units_per_carton');
            }
        });
    }
};
