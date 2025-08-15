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
        Schema::table('warehouse_inventory', function (Blueprint $table) {
            // إضافة فهرس مركب للاستعلامات السريعة
            $table->index(['warehouse_id', 'product_id'], 'idx_warehouse_product');
            $table->index(['min_threshold', 'closed_cartons', 'loose_units'], 'idx_threshold_stock');
        });

        Schema::table('movements', function (Blueprint $table) {
            // تحسين فهرس movements للتقارير
            $table->index(['warehouse_id', 'created_at'], 'idx_warehouse_date');
            $table->index(['type', 'created_at'], 'idx_type_date');
        });

        Schema::table('products', function (Blueprint $table) {
            // فهرس للبحث في أسماء المنتجات
            $table->index(['active', 'name'], 'idx_active_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_inventory', function (Blueprint $table) {
            $table->dropIndex('idx_warehouse_product');
            $table->dropIndex('idx_threshold_stock');
        });

        Schema::table('movements', function (Blueprint $table) {
            $table->dropIndex('idx_warehouse_date');
            $table->dropIndex('idx_type_date');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_active_name');
        });
    }
};
