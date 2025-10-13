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
        Schema::create('issue_voucher_items', function (Blueprint $table) {
            $table->id();
            
            // الإذن المرتبط به
            $table->foreignId('issue_voucher_id')
                ->constrained('issue_vouchers')
                ->cascadeOnDelete()
                ->comment('إذن الصرف');
            
            // المنتج
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete()
                ->comment('المنتج');
            
            $table->decimal('quantity', 10, 2)->comment('الكمية المصروفة');
            $table->decimal('unit_price', 10, 2)->comment('سعر الوحدة');
            $table->decimal('total', 12, 2)->comment('الإجمالي (الكمية × السعر)');
            
            $table->timestamps();

            // الفهارس
            $table->index('issue_voucher_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_voucher_items');
    }
};
