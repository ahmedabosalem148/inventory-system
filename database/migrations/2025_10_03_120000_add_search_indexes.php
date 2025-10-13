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
        // Indexes للبحث السريع في Products
        Schema::table('products', function (Blueprint $table) {
            $table->index('sku', 'idx_products_sku');
            $table->index('name', 'idx_products_name');
            $table->index(['sku', 'name'], 'idx_products_sku_name');
            $table->index(['is_active', 'name'], 'idx_products_active_name');
        });

        // Indexes للبحث السريع في Customers
        Schema::table('customers', function (Blueprint $table) {
            $table->index('code', 'idx_customers_code');
            $table->index('name', 'idx_customers_name');
            $table->index(['code', 'name'], 'idx_customers_code_name');
            $table->index(['is_active', 'name'], 'idx_customers_active_name');
        });

        // Indexes للمخزون (للبحث حسب الفرع)
        if (Schema::hasTable('product_branch')) {
            Schema::table('product_branch', function (Blueprint $table) {
                $table->index(['branch_id', 'product_id'], 'idx_pb_branch_product');
                $table->index(['product_id', 'current_qty'], 'idx_pb_product_qty');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_sku');
            $table->dropIndex('idx_products_name');
            $table->dropIndex('idx_products_sku_name');
            $table->dropIndex('idx_products_active_name');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_code');
            $table->dropIndex('idx_customers_name');
            $table->dropIndex('idx_customers_code_name');
            $table->dropIndex('idx_customers_active_name');
        });

        if (Schema::hasTable('product_branch')) {
            Schema::table('product_branch', function (Blueprint $table) {
                $table->dropIndex('idx_pb_branch_product');
                $table->dropIndex('idx_pb_product_qty');
            });
        }
    }
};
