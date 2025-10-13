<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * جدول ربط السدادات بالإذونات (Pivot Table)
     * يسمح بتوزيع سداد واحد على أكثر من إذن
     */
    public function up(): void
    {
        Schema::create('payment_voucher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->enum('voucher_type', ['issue_voucher', 'return_voucher'])->comment('نوع الإذن');
            $table->unsignedBigInteger('voucher_id')->comment('معرف الإذن');
            $table->decimal('allocated_amount', 12, 2)->comment('المبلغ المخصص من السداد لهذا الإذن');
            $table->timestamps();
            
            // Indexes
            $table->index(['voucher_type', 'voucher_id']);
            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_voucher');
    }
};
