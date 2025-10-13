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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Customer relationship
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete()
                ->comment('العميل');
            
            // Payment details
            $table->date('payment_date')->comment('تاريخ السداد');
            $table->decimal('amount', 12, 2)->comment('المبلغ');
            
            $table->enum('payment_method', ['CASH', 'CHEQUE'])
                ->comment('طريقة الدفع');
            
            // Cheque relationship (if payment method is CHEQUE)
            $table->foreignId('cheque_id')
                ->nullable()
                ->constrained('cheques')
                ->nullOnDelete()
                ->comment('الشيك (إن كان الدفع بشيك)');
            
            // Optional reference to issue voucher
            $table->foreignId('issue_voucher_id')
                ->nullable()
                ->constrained('issue_vouchers')
                ->nullOnDelete()
                ->comment('إذن الصرف المرتبط (اختياري)');
            
            $table->text('notes')->nullable()->comment('ملاحظات');
            
            // User who recorded the payment
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('المستخدم المسجل');
            
            $table->timestamps();
            
            // Indexes
            $table->index('customer_id');
            $table->index('payment_date');
            $table->index('payment_method');
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
