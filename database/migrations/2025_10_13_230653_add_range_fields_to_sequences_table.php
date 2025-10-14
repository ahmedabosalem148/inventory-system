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
            // Add range and configuration fields
            $table->string('prefix')->nullable()->after('entity_type');
            $table->integer('min_value')->default(1)->after('last_number');
            $table->integer('max_value')->default(999999)->after('min_value');
            $table->integer('increment_by')->default(1)->after('max_value');
            $table->boolean('auto_reset')->default(true)->after('increment_by');
            
            // Add index for better performance
            $table->index(['entity_type', 'year', 'last_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->dropIndex(['entity_type', 'year', 'last_number']);
            $table->dropColumn(['prefix', 'min_value', 'max_value', 'increment_by', 'auto_reset']);
        });
    }
};
