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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('كود العميل');
            $table->string('name', 200)->comment('اسم العميل');
            $table->enum('type', ['retail', 'wholesale'])->default('retail')->comment('نوع العميل: قطاعي/جملة');
            $table->string('phone', 20)->nullable()->comment('رقم الهاتف');
            $table->text('address')->nullable()->comment('العنوان');
            $table->decimal('balance', 12, 2)->default(0)->comment('الرصيد الحالي (موجب = له, سالب = عليه)');
            $table->boolean('is_active')->default(true)->comment('نشط/غير نشط');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            $table->index('name');
            $table->index('phone');
            $table->index('is_active');
            $table->index('balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
