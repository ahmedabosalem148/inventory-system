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
        // إضافة حقول الخصومات لجدول issue_vouchers (خصم الفاتورة)
        Schema::table('issue_vouchers', function (Blueprint $table) {
            // خصم على مستوى الفاتورة
            $table->enum('discount_type', ['none', 'percentage', 'fixed'])
                ->default('none')
                ->after('total_amount')
                ->comment('نوع الخصم: none=بدون، percentage=نسبة، fixed=مبلغ ثابت');
            
            $table->decimal('discount_value', 10, 2)
                ->default(0)
                ->after('discount_type')
                ->comment('قيمة الخصم (النسبة أو المبلغ)');
            
            $table->decimal('discount_amount', 12, 2)
                ->default(0)
                ->after('discount_value')
                ->comment('مبلغ الخصم المحسوب');
            
            $table->decimal('subtotal', 12, 2)
                ->default(0)
                ->after('discount_amount')
                ->comment('المجموع قبل الخصم');
            
            $table->decimal('net_total', 12, 2)
                ->default(0)
                ->after('subtotal')
                ->comment('الصافي بعد الخصم');
        });

        // إضافة حقول الخصومات لجدول issue_voucher_items (خصم البند)
        Schema::table('issue_voucher_items', function (Blueprint $table) {
            // خصم على مستوى البند
            $table->enum('discount_type', ['none', 'percentage', 'fixed'])
                ->default('none')
                ->after('total_price')
                ->comment('نوع خصم البند');
            
            $table->decimal('discount_value', 10, 2)
                ->default(0)
                ->after('discount_type')
                ->comment('قيمة خصم البند');
            
            $table->decimal('discount_amount', 12, 2)
                ->default(0)
                ->after('discount_value')
                ->comment('مبلغ خصم البند المحسوب');
            
            $table->decimal('net_price', 12, 2)
                ->default(0)
                ->after('discount_amount')
                ->comment('صافي السعر بعد خصم البند');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'discount_type',
                'discount_value',
                'discount_amount',
                'subtotal',
                'net_total',
            ]);
        });

        Schema::table('issue_voucher_items', function (Blueprint $table) {
            $table->dropColumn([
                'discount_type',
                'discount_value',
                'discount_amount',
                'net_price',
            ]);
        });
    }
};
