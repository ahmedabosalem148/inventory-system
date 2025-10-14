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
        Schema::table('issue_vouchers', function (Blueprint $table) {
            // تحديد ما إذا كان هذا الإذن تحويل بين فروع
            $table->boolean('is_transfer')->default(false)->after('notes')->comment('هل هو تحويل بين فروع؟');
            
            // الفرع المستهدف (في حالة التحويل)
            $table->foreignId('target_branch_id')
                ->nullable()
                ->after('is_transfer')
                ->constrained('branches')
                ->restrictOnDelete()
                ->comment('الفرع المستهدف (في حالة التحويل)');
            
            // فهرس للتحويلات
            $table->index(['is_transfer', 'target_branch_id'], 'idx_transfers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->dropIndex('idx_transfers');
            $table->dropForeign(['target_branch_id']);
            $table->dropColumn(['is_transfer', 'target_branch_id']);
        });
    }
};
