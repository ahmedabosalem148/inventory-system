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
        // Add missing fields to return_vouchers table
        Schema::table('return_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('return_vouchers', 'voucher_type')) {
                $table->enum('voucher_type', ['cash', 'credit'])->default('cash')->after('return_date')->comment('نوع المرتجع');
            }
            if (!Schema::hasColumn('return_vouchers', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('voucher_type')->comment('المجموع قبل الخصم');
            }
            if (!Schema::hasColumn('return_vouchers', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal')->comment('مبلغ الخصم');
            }
            if (!Schema::hasColumn('return_vouchers', 'net_total')) {
                $table->decimal('net_total', 12, 2)->default(0)->after('discount_amount')->comment('الصافي');
            }
            if (!Schema::hasColumn('return_vouchers', 'reason')) {
                $table->text('reason')->nullable()->after('status')->comment('سبب الإرجاع');
            }
            if (!Schema::hasColumn('return_vouchers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('reason')->comment('تاريخ الاعتماد');
            }
            if (!Schema::hasColumn('return_vouchers', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete()->comment('من اعتمد');
            }
        });

        // Add transaction_date to ledger_entries table
        Schema::table('ledger_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('ledger_entries', 'transaction_date')) {
                $table->date('transaction_date')->nullable()->after('amount')->comment('تاريخ العملية');
                $table->index('transaction_date');
            }
        });
        
        // Check if voucher_type exists in issue_vouchers, if not add it
        Schema::table('issue_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('issue_vouchers', 'voucher_type')) {
                $table->enum('voucher_type', ['cash', 'credit'])->default('cash')->after('issue_date')->comment('نوع الفاتورة');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove fields from return_vouchers
        Schema::table('return_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('return_vouchers', 'voucher_type')) {
                $table->dropColumn('voucher_type');
            }
            if (Schema::hasColumn('return_vouchers', 'subtotal')) {
                $table->dropColumn('subtotal');
            }
            if (Schema::hasColumn('return_vouchers', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
            if (Schema::hasColumn('return_vouchers', 'net_total')) {
                $table->dropColumn('net_total');
            }
            if (Schema::hasColumn('return_vouchers', 'reason')) {
                $table->dropColumn('reason');
            }
            if (Schema::hasColumn('return_vouchers', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('return_vouchers', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
        });

        // Remove transaction_date from ledger_entries
        Schema::table('ledger_entries', function (Blueprint $table) {
            if (Schema::hasColumn('ledger_entries', 'transaction_date')) {
                $table->dropIndex(['transaction_date']);
                $table->dropColumn('transaction_date');
            }
        });
        
        // Remove voucher_type from issue_vouchers if exists
        Schema::table('issue_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('issue_vouchers', 'voucher_type')) {
                $table->dropColumn('voucher_type');
            }
        });
    }
};
