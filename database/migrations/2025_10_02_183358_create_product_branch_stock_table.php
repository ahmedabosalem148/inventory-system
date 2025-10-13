<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * من MIGRATIONS-ORDER.md: Migration 6 - product_branch_stock
     * جدول ربط المنتجات بالفروع مع تتبع المخزون
     */
    public function up(): void
    {
        Schema::create('product_branch_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->integer('current_stock')->default(0); // الكمية الحالية
            $table->integer('reserved_stock')->default(0); // الكمية المحجوزة
            $table->timestamps();

            // منع تكرار المنتج في نفس الفرع
            $table->unique(['product_id', 'branch_id']);
            
            $table->index('product_id');
            $table->index('branch_id');
            $table->index('current_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_branch_stock');
    }
};
