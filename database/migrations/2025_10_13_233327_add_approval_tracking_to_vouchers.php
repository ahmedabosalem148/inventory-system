<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add approval tracking fields to both issue_vouchers and return_vouchers.
     * We'll use the existing 'completed' status but add approval tracking.
     */
    public function up(): void
    {
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('status')->comment('تاريخ الاعتماد والترقيم');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete()->comment('معتمد بواسطة');
        });
        
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('status')->comment('تاريخ الاعتماد والترقيم');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete()->comment('معتمد بواسطة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
        });
        
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
        });
    }
};
