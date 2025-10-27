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
            $table->string('reason', 500)->after('notes');
            $table->enum('reason_category', ['damaged', 'defective', 'customer_request', 'wrong_item', 'other'])
                ->nullable()
                ->after('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_vouchers', function (Blueprint $table) {
            $table->dropColumn(['reason', 'reason_category']);
        });
    }
};
