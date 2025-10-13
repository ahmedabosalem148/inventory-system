<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * إضافة حقول ربط المستخدمين بالمخازن
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('assigned_branch_id')
                  ->nullable()
                  ->after('password')
                  ->constrained('branches')
                  ->nullOnDelete()
                  ->comment('المخزن الافتراضي للمستخدم');
                  
            $table->foreignId('current_branch_id')
                  ->nullable()
                  ->after('assigned_branch_id')
                  ->constrained('branches')
                  ->nullOnDelete()
                  ->comment('المخزن النشط حاليًا - يتغير عند التبديل');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_branch_id']);
            $table->dropForeign(['assigned_branch_id']);
            $table->dropColumn(['current_branch_id', 'assigned_branch_id']);
        });
    }
};
