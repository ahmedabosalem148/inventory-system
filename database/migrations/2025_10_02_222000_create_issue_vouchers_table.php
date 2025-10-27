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
        Schema::create('issue_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number', 50)->unique()->comment('رقم الإذن (مثل: ISS-00001)');
            
            // العميل (اختياري - يمكن أن يكون عميل كاش)
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->comment('العميل المسجل (nullable للعملاء النقديين)');
            
            $table->string('customer_name', 200)->nullable()->comment('اسم عميل الكاش');
            
            // الفرع الذي تم الصرف منه
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete()
                ->comment('الفرع');
            
            $table->date('issue_date')->comment('تاريخ الصرف');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('إجمالي المبلغ');
            
            $table->enum('status', ['PENDING', 'APPROVED', 'completed', 'cancelled'])->default('PENDING')->comment('الحالة');
            
            // من قام بإنشاء الإذن
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('المستخدم الذي أنشأ الإذن');
            
            $table->timestamps();

            // الفهارس
            $table->index('voucher_number');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('issue_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_vouchers');
    }
};
