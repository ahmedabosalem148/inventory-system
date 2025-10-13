<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration 1: create_branches_table
     * من المواصفات: "branches : (code, name, is_active)"
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('كود الفرع');
            $table->string('name', 100)->comment('اسم الفرع');
            $table->text('location')->nullable()->comment('موقع الفرع');
            $table->boolean('is_active')->default(true)->comment('حالة التفعيل');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
