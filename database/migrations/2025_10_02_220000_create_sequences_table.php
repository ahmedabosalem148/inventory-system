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
        // Drop existing table if it exists
        Schema::dropIfExists('sequences');
        
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // issue_vouchers, return_vouchers, etc.
            $table->integer('year'); // 2025
            $table->integer('last_number')->default(0); // Last used number
            $table->timestamps();

            // Ensure unique sequence per entity type per year
            $table->unique(['entity_type', 'year']);
            
            // Index for faster lookups
            $table->index(['entity_type', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
