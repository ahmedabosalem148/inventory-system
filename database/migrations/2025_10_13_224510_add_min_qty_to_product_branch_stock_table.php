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
            // إضافة الحد الأدنى لكل منتج في كل فرع
            $table->integer('min_qty')->default(0)->after('reserved_stock');
            
            // إضافة فهرس للبحث السريع عن المنتجات المنخفضة
            $table->index(['current_stock', 'min_qty']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_branch_stock', function (Blueprint $table) {
            $table->dropIndex(['current_stock', 'min_qty']);
            $table->dropColumn('min_qty');
        });
    }
};
