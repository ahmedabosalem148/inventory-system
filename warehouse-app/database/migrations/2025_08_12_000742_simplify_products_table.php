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
        Schema::table('products', function (Blueprint $table) {
            // Drop the new columns we just added
            $table->dropColumn(['name_en', 'sku', 'unit_price', 'category', 'description']);
            
            // Rename name_ar to name
            $table->renameColumn('name_ar', 'name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Reverse the changes
            $table->renameColumn('name', 'name_ar');
            $table->string('name_en')->nullable();
            $table->string('sku', 100)->nullable()->unique();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->string('category', 50)->nullable();
            $table->text('description')->nullable();
        });
    }
};
