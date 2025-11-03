<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * إضافة طرق دفع جديدة: Vodafone Cash, InstaPay, Bank Account
     * 
     * ملاحظة: SQLite لا يدعم ALTER COLUMN بشكل مباشر
     * MySQL/MariaDB تدعم ENUM modification
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: لا يحتاج تعديل - يقبل أي قيمة نصية
            // Validation يتم في Application Layer (Form Requests)
            return;
        }
        
        // MySQL/MariaDB: تعديل ENUM لإضافة الطرق الجديدة
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('CASH', 'CHEQUE', 'VODAFONE_CASH', 'INSTAPAY', 'BANK_ACCOUNT') NOT NULL COMMENT 'طريقة الدفع'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            return;
        }
        
        // MySQL/MariaDB: العودة للقيم الأصلية فقط
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('CASH', 'CHEQUE') NOT NULL COMMENT 'طريقة الدفع'");
    }
};
