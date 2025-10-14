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
        Schema::table('return_vouchers', function (Blueprint $table) {
            // Add reason field for return
            $table->string('reason')->nullable()->after('notes')->comment('سبب الإرجاع');
            
            // Add approval tracking fields
            $table->timestamp('approved_at')->nullable()->after('reason')->comment('تاريخ الاعتماد');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('المستخدم الذي اعتمد الإذن');
            
            // Add index for approved_at
            $table->index('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_at', 'approved_by', 'reason']);
        });
    }
};
