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
            $table->enum('product_classification', [
                'finished_product',
                'semi_finished',
                'raw_material',
                'parts',
                'plastic_parts',
                'aluminum_parts',
                'other'
            ])->default('finished_product')->after('category_id');
            
            $table->index('product_classification', 'idx_products_classification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_classification');
            $table->dropColumn('product_classification');
        });
    }
};
