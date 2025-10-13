<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * جدول صلاحيات المستخدمين على المخازن
     */
    public function up(): void
    {
        Schema::create('user_branch_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
                  
            $table->foreignId('branch_id')
                  ->constrained('branches')
                  ->onDelete('cascade');
                  
            $table->enum('permission_level', ['view_only', 'full_access'])
                  ->default('view_only');
                  
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['user_id', 'branch_id']);
            
            // Indexes
            $table->index(['user_id', 'permission_level']);
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_branch_permissions');
    }
};
