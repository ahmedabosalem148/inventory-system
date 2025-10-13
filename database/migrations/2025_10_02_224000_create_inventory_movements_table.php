<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration 12: create_inventory_movements_table
     * سجل كامل لكل حركة مخزنية: إضافة، صرف، مرتجع، تحويل
     */
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->comment('الفرع المرتبط بالحركة');
            
            $table->foreignId('product_id')
                ->constrained('products')
                ->comment('المنتج المرتبط بالحركة');
            
            $table->enum('movement_type', ['ADD', 'ISSUE', 'RETURN', 'TRANSFER_OUT', 'TRANSFER_IN'])
                ->comment('نوع الحركة');
            
            $table->integer('qty_units')
                ->comment('الكمية (+ إضافة، - خصم)');
            
            $table->decimal('unit_price_snapshot', 12, 2)
                ->nullable()
                ->comment('سعر الوحدة وقت الحركة');
            
            $table->string('ref_table', 50)
                ->nullable()
                ->comment('الجدول المرجعي (issue_vouchers, return_vouchers...)');
            
            $table->unsignedBigInteger('ref_id')
                ->nullable()
                ->comment('معرّف السجل المرجعي');
            
            $table->text('notes')
                ->nullable()
                ->comment('ملاحظات');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['branch_id', 'product_id', 'created_at'], 'idx_inventory_branch_product_date');
            $table->index(['ref_table', 'ref_id'], 'idx_inventory_ref');
            $table->index('movement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
