<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * إنشاء جدول دفتر العملاء (علية/له)
     * يطبق نظام المحاسبة ذات القيد المزدوج (Double Entry Bookkeeping)
     * مطابق تماماً لملف Excel الحالي
     */
    public function up(): void
    {
        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            
            // العميل المرتبط
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('cascade')
                  ->comment('معرف العميل');
            
            // تاريخ القيد
            $table->date('entry_date')
                  ->comment('تاريخ القيد المحاسبي');
            
            // وصف القيد
            $table->string('description')
                  ->comment('وصف القيد (مثال: فاتورة رقم 4134، دفعة نقدية، ارتجاع)');
            
            // علية (مديونية على العميل - Debit)
            $table->decimal('debit_aliah', 12, 2)
                  ->default(0)
                  ->comment('علية - المبالغ المستحقة على العميل (مبيعات آجلة)');
            
            // له (دائنية للعميل - Credit)
            $table->decimal('credit_lah', 12, 2)
                  ->default(0)
                  ->comment('له - المبالغ المدفوعة أو المرتجعة (خصم من المديونية)');
            
            // المرجع (ربط مع المستند المصدر)
            $table->string('ref_table', 50)
                  ->nullable()
                  ->comment('اسم جدول المستند المصدر (issue_vouchers, return_vouchers, payments)');
            
            $table->unsignedBigInteger('ref_id')
                  ->nullable()
                  ->comment('معرف المستند المصدر');
            
            // ملاحظات إضافية
            $table->text('notes')
                  ->nullable()
                  ->comment('ملاحظات إضافية على القيد');
            
            // من أنشأ القيد (للتدقيق)
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('المستخدم الذي أنشأ القيد');
            
            $table->timestamps();
            
            // الفهارس لتحسين الأداء
            $table->index(['customer_id', 'entry_date'], 'idx_customer_date');
            $table->index(['ref_table', 'ref_id'], 'idx_ref');
            $table->index('entry_date', 'idx_entry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ledger_entries');
    }
};
