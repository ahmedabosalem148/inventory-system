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
        Schema::table('sequences', function (Blueprint $table) {
            $table->string('prefix')->nullable()->after('year');
            $table->integer('min_value')->default(1)->after('last_number');
            $table->integer('max_value')->default(999999)->after('min_value');
            $table->integer('increment_by')->default(1)->after('max_value');
            $table->boolean('auto_reset')->default(false)->after('increment_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->dropColumn(['prefix', 'min_value', 'max_value', 'increment_by', 'auto_reset']);
        });
    }
};
