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
        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained('inventory_counts')->cascadeOnDelete()->comment('Parent count');
            $table->foreignId('product_id')->constrained('products')->comment('Product being counted');
            $table->decimal('system_quantity', 15, 3)->default(0)->comment('Quantity in system');
            $table->decimal('physical_quantity', 15, 3)->default(0)->comment('Actual counted quantity');
            $table->decimal('difference', 15, 3)->default(0)->comment('Difference (physical - system)');
            $table->text('notes')->nullable()->comment('Item-specific notes');
            $table->timestamps();
            
            // Indexes
            $table->index('inventory_count_id');
            $table->index('product_id');
            
            // Unique constraint: one product per count
            $table->unique(['inventory_count_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');
    }
};
