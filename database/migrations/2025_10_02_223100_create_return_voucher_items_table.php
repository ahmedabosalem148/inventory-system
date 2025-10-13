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
        Schema::create('return_voucher_items', function (Blueprint $table) {
            $table->id();
            
            // Return voucher relationship
            $table->foreignId('return_voucher_id')
                ->constrained('return_vouchers')
                ->cascadeOnDelete()
                ->comment('إذن الإرجاع');
            
            // Product relationship
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete()
                ->comment('المنتج');
            
            $table->integer('quantity')->comment('الكمية');
            $table->decimal('unit_price', 10, 2)->comment('سعر الوحدة');
            $table->decimal('total_price', 12, 2)->comment('إجمالي السطر');
            
            $table->timestamps();
            
            // Indexes
            $table->index('return_voucher_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_voucher_items');
    }
};
