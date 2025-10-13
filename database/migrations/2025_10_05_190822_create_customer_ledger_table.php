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
        Schema::create('customer_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('transaction_type', 50); // issue_voucher, return_voucher, payment, discount, adjustment
            $table->string('reference_number', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of related voucher/payment
            $table->date('transaction_date');
            $table->decimal('debit', 12, 2)->default(0); // له (مدين)
            $table->decimal('credit', 12, 2)->default(0); // منه (دائن)
            $table->decimal('balance', 12, 2)->default(0); // الرصيد الجاري
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('customer_id');
            $table->index('transaction_date');
            $table->index(['customer_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ledger');
    }
};
