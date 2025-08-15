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
        Schema::create('warehouse_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->integer('closed_cartons')->unsigned()->default(0);
            $table->integer('loose_units')->unsigned()->default(0);
            $table->integer('min_threshold')->unsigned()->default(0);
            $table->integer('version')->default(1);
            $table->timestamps();
            
            $table->unique(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory');
    }
};
