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
            // إضافة حقل pack_size للمنتج - عدد القطع في الكرتونة/العبوة
            $table->integer('pack_size')->default(1)->after('unit');
            
            // إضافة فهرس للبحث السريع
            $table->index('pack_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // حذف الفهرس أولاً ثم الحقل
            $table->dropIndex(['pack_size']);
            $table->dropColumn('pack_size');
        });
    }
};
