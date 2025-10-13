<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * جدول السدادات - يخزن جميع السدادات من العملاء
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique()->comment('رقم السداد');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->date('payment_date')->comment('تاريخ السداد');
            $table->enum('payment_method', ['cash', 'cheque', 'bank_transfer'])->default('cash')->comment('طريقة السداد');
            $table->decimal('amount', 12, 2)->comment('المبلغ');
            
            // معلومات الشيك (إذا كانت طريقة السداد شيك)
            $table->string('cheque_number', 50)->nullable()->comment('رقم الشيك');
            $table->date('cheque_date')->nullable()->comment('تاريخ الشيك');
            $table->string('bank_name', 100)->nullable()->comment('اسم البنك');
            $table->enum('cheque_status', ['pending', 'cleared', 'returned'])->nullable()->comment('حالة الشيك');
            
            // معلومات التحويل البنكي
            $table->string('transfer_reference', 100)->nullable()->comment('رقم التحويل');
            
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->foreignId('branch_id')->constrained('branches')->comment('الفرع');
            $table->foreignId('created_by')->constrained('users')->comment('المستخدم المسجل');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('payment_date');
            $table->index('customer_id');
            $table->index(['payment_method', 'payment_date']);
            $table->index('cheque_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
