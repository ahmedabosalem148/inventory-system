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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            
            $table->integer('quantity_ordered')->default(0); // الكمية المطلوبة
            $table->integer('quantity_received')->default(0); // الكمية المستلمة
            $table->decimal('unit_price', 10, 2); // سعر الوحدة
            
            // Line discount
            $table->enum('discount_type', ['NONE', 'PERCENTAGE', 'FIXED'])->default('NONE');
            $table->decimal('discount_value', 8, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            
            $table->decimal('subtotal', 12, 2)->default(0); // المجموع قبل الخصم
            $table->decimal('total', 12, 2)->default(0); // المجموع بعد الخصم
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('purchase_order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
