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
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Unique count code (e.g., IC-2025-0001)');
            $table->foreignId('branch_id')->constrained('branches')->comment('Branch where count is performed');
            $table->date('count_date')->comment('Date of physical count');
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED'])->default('DRAFT')->comment('Count status');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who created count');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who approved/rejected');
            $table->timestamp('approved_at')->nullable()->comment('Approval/rejection timestamp');
            $table->text('notes')->nullable()->comment('General notes');
            $table->text('rejection_reason')->nullable()->comment('Reason if rejected');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('branch_id');
            $table->index('status');
            $table->index('count_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_counts');
    }
};
