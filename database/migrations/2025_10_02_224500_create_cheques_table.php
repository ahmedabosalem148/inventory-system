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
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            
            // Customer relationship
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete()
                ->comment('العميل');
            
            // Cheque details
            $table->string('cheque_number')->comment('رقم الشيك');
            $table->string('bank_name')->comment('اسم البنك');
            $table->date('due_date')->comment('تاريخ الاستحقاق');
            $table->decimal('amount', 12, 2)->comment('مبلغ الشيك');
            
            // Cheque status
            $table->enum('status', ['PENDING', 'CLEARED', 'RETURNED'])
                ->default('PENDING')
                ->comment('حالة الشيك');
            
            $table->date('cleared_at')->nullable()->comment('تاريخ الصرف');
            $table->text('return_reason')->nullable()->comment('سبب الارتجاع (إن تم)');
            
            // Optional link to issue voucher
            $table->foreignId('issue_voucher_id')
                ->nullable()
                ->constrained('issue_vouchers')
                ->nullOnDelete()
                ->comment('إذن الصرف المرتبط (اختياري)');
            
            $table->text('notes')->nullable()->comment('ملاحظات');
            
            // User who created/updated the cheque
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('المستخدم المسجل');
            
            $table->foreignId('cleared_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('من قام بالصرف');
            
            $table->timestamps();
            
            // Indexes
            $table->index('customer_id');
            $table->index('cheque_number');
            $table->index('status');
            $table->index('due_date');
            $table->index(['status', 'due_date']); // Composite for pending cheques report
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
