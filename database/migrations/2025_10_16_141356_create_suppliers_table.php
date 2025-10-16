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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // اسم المورد
            $table->string('contact_name', 100)->nullable(); // اسم الشخص المسؤول
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number', 50)->nullable(); // الرقم الضريبي
            $table->enum('payment_terms', ['CASH', 'NET_7', 'NET_15', 'NET_30', 'NET_60'])
                  ->default('NET_30'); // شروط الدفع
            $table->decimal('credit_limit', 12, 2)->default(0); // حد الائتمان
            $table->decimal('current_balance', 12, 2)->default(0); // الرصيد الحالي
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes(); // للحذف المنطقي
            
            // Indexes
            $table->index('name');
            $table->index('status');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
