<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            // إضافة عمود لتاريخ العملية منفصل (للتقارير اليومية)
            $table->date('operation_date')->after('created_at')->nullable()->index();
            
            // إضافة معلومات إضافية
            $table->decimal('unit_cost', 10, 2)->nullable()->after('quantity_units')->comment('تكلفة الوحدة وقت العملية');
            $table->decimal('total_value', 10, 2)->nullable()->after('unit_cost')->comment('القيمة الإجمالية للعملية');
            
            // تحديث عمود الملاحظات ليكون أطول
            $table->text('notes')->nullable()->after('note')->comment('ملاحظات العملية');
            
            // فهارس للتقارير
            $table->index(['type', 'operation_date']);
            $table->index(['product_id', 'operation_date']);
            $table->index(['warehouse_id', 'operation_date']);
        });
        
        // نسخ البيانات من note إلى notes
        DB::statement("UPDATE movements SET notes = note WHERE notes IS NULL AND note IS NOT NULL");
        
        // تحديث البيانات الموجودة
        DB::statement("UPDATE movements SET operation_date = DATE(created_at) WHERE operation_date IS NULL");
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropIndex(['type', 'operation_date']);
            $table->dropIndex(['product_id', 'operation_date']);
            $table->dropIndex(['warehouse_id', 'operation_date']);
            
            $table->dropColumn(['operation_date', 'unit_cost', 'total_value', 'notes']);
        });
    }
};
