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
        Schema::create('return_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique()->comment('رقم إذن الإرجاع');
            
            // Customer relationship (nullable for cash returns)
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->comment('العميل (اختياري للمرتجعات النقدية)');
            
            $table->string('customer_name')->nullable()->comment('اسم العميل (للمرتجعات النقدية)');
            
            // Branch relationship
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete()
                ->comment('الفرع/المخزن');
            
            $table->date('return_date')->comment('تاريخ الإرجاع');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('إجمالي المبلغ');
            
            // Status
            $table->enum('status', ['completed', 'cancelled'])
                ->default('completed')
                ->comment('حالة الإذن');
            
            $table->text('notes')->nullable()->comment('ملاحظات');
            
            // User who created the voucher
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('المستخدم المسجل');
            
            $table->timestamps();
            
            // Indexes
            $table->index('voucher_number');
            $table->index('return_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_vouchers');
    }
};
