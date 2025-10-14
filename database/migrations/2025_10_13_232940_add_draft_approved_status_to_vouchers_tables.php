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
     * إضافة حالات جديدة للأذونات:
     * - draft: مسودة (لم يتم اعتمادها بعد)  
     * - approved: معتمدة (تم منح رقم تسلسلي)
     * - completed: مكتملة (تم تنفيذها)
     * - cancelled: ملغاة
     */
    public function up(): void
    {
        // For SQLite compatibility, we'll add new columns and then migrate data
        Schema::table('issue_vouchers', function (Blueprint $table) {
            // Add new status column
            $table->enum('new_status', ['draft', 'approved', 'completed', 'cancelled'])
                ->default('draft')->after('status')->comment('الحالة الجديدة');
            
            // Add approval tracking fields
            $table->timestamp('approved_at')->nullable()->after('new_status')->comment('تاريخ الاعتماد');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete()->comment('معتمد بواسطة');
        });
        
        Schema::table('return_vouchers', function (Blueprint $table) {
            // Add new status column
            $table->enum('new_status', ['draft', 'approved', 'completed', 'cancelled'])
                ->default('draft')->after('status')->comment('الحالة الجديدة');
            
            // Add approval tracking fields
            $table->timestamp('approved_at')->nullable()->after('new_status')->comment('تاريخ الاعتماد');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete()->comment('معتمد بواسطة');
        });
        
        // Migrate existing data: map 'completed' to 'approved' (since they have voucher numbers)
        DB::table('issue_vouchers')->update(['new_status' => 'approved']);
        DB::table('return_vouchers')->update(['new_status' => 'approved']);
        
        // Drop old status column and rename new one
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        // Rename new_status to status
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->renameColumn('new_status', 'status');
        });
        
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->renameColumn('new_status', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back old status columns
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->enum('old_status', ['completed', 'cancelled'])
                ->default('completed')->after('status');
        });
        
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->enum('old_status', ['completed', 'cancelled'])
                ->default('completed')->after('status');
        });
        
        // Migrate data back
        DB::table('issue_vouchers')
            ->whereIn('status', ['approved', 'completed'])
            ->update(['old_status' => 'completed']);
        DB::table('issue_vouchers')
            ->where('status', 'cancelled')
            ->update(['old_status' => 'cancelled']);
            
        DB::table('return_vouchers')
            ->whereIn('status', ['approved', 'completed'])
            ->update(['old_status' => 'completed']);
        DB::table('return_vouchers')
            ->where('status', 'cancelled')
            ->update(['old_status' => 'cancelled']);
        
        // Remove new fields
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approved_at', 'status']);
        });
        
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approved_at', 'status']);
        });
        
        // Rename back
        Schema::table('issue_vouchers', function (Blueprint $table) {
            $table->renameColumn('old_status', 'status');
        });
        
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->renameColumn('old_status', 'status');
        });
    }
};
