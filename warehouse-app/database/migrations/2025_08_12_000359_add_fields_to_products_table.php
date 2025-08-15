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
        Schema::table('products', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('sku', 100)->nullable()->unique()->after('name_en');
            $table->decimal('unit_price', 10, 2)->nullable()->after('carton_size');
            $table->string('category', 50)->nullable()->after('unit_price');
            $table->text('description')->nullable()->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'sku', 'unit_price', 'category', 'description']);
        });
    }
};
