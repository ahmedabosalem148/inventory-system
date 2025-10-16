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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique(); // رقم أمر الشراء
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict'); // الفرع المستلم
            $table->date('order_date'); // تاريخ الطلب
            $table->date('expected_delivery_date')->nullable(); // التاريخ المتوقع للتسليم
            $table->date('actual_delivery_date')->nullable(); // التاريخ الفعلي للتسليم
            
            // Amounts
            $table->decimal('subtotal', 12, 2)->default(0); // المجموع قبل الخصم
            $table->enum('discount_type', ['NONE', 'PERCENTAGE', 'FIXED'])->default('NONE');
            $table->decimal('discount_value', 8, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0); // نسبة الضريبة
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0); // تكلفة الشحن
            $table->decimal('total_amount', 12, 2)->default(0); // الإجمالي النهائي
            
            // Status tracking
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'CANCELLED'])->default('DRAFT');
            $table->enum('receiving_status', ['NOT_RECEIVED', 'PARTIALLY_RECEIVED', 'FULLY_RECEIVED'])
                  ->default('NOT_RECEIVED');
            $table->enum('payment_status', ['UNPAID', 'PARTIALLY_PAID', 'PAID'])->default('UNPAID');
            
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('order_number');
            $table->index('supplier_id');
            $table->index('branch_id');
            $table->index('order_date');
            $table->index(['status', 'receiving_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
