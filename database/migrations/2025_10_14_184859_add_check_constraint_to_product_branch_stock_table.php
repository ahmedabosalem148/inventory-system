<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * TASK-B03: Negative Stock Prevention
     * إضافة CHECK constraint على current_stock لمنع القيم السالبة على مستوى قاعدة البيانات
     */
    public function up(): void
    {
        // SQLite: CHECK constraint inline
        DB::statement('
            CREATE TABLE IF NOT EXISTS product_branch_stock_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                branch_id INTEGER NOT NULL,
                current_stock INTEGER DEFAULT 0 CHECK(current_stock >= 0),
                reserved_stock INTEGER DEFAULT 0 CHECK(reserved_stock >= 0),
                min_qty INTEGER DEFAULT 0,
                min_stock INTEGER DEFAULT 10,
                max_stock INTEGER,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY(branch_id) REFERENCES branches(id) ON DELETE CASCADE,
                UNIQUE(product_id, branch_id)
            )
        ');
        
        // Copy data from old table
        DB::statement('
            INSERT INTO product_branch_stock_new 
            SELECT * FROM product_branch_stock
        ');
        
        // Drop old table
        DB::statement('DROP TABLE product_branch_stock');
        
        // Rename new table
        DB::statement('ALTER TABLE product_branch_stock_new RENAME TO product_branch_stock');
        
        // Recreate indexes
        DB::statement('CREATE INDEX product_branch_stock_product_id_index ON product_branch_stock(product_id)');
        DB::statement('CREATE INDEX product_branch_stock_branch_id_index ON product_branch_stock(branch_id)');
        DB::statement('CREATE INDEX product_branch_stock_current_stock_index ON product_branch_stock(current_stock)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا يمكن إزالة CHECK constraint في SQLite
        // يجب إعادة إنشاء الجدول بدون constraint
        DB::statement('
            CREATE TABLE IF NOT EXISTS product_branch_stock_old (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                branch_id INTEGER NOT NULL,
                current_stock INTEGER DEFAULT 0,
                reserved_stock INTEGER DEFAULT 0,
                min_qty INTEGER DEFAULT 0,
                min_stock INTEGER DEFAULT 10,
                max_stock INTEGER,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY(branch_id) REFERENCES branches(id) ON DELETE CASCADE,
                UNIQUE(product_id, branch_id)
            )
        ');
        
        DB::statement('INSERT INTO product_branch_stock_old SELECT * FROM product_branch_stock');
        DB::statement('DROP TABLE product_branch_stock');
        DB::statement('ALTER TABLE product_branch_stock_old RENAME TO product_branch_stock');
        
        // Recreate indexes
        DB::statement('CREATE INDEX product_branch_stock_product_id_index ON product_branch_stock(product_id)');
        DB::statement('CREATE INDEX product_branch_stock_branch_id_index ON product_branch_stock(branch_id)');
        DB::statement('CREATE INDEX product_branch_stock_current_stock_index ON product_branch_stock(current_stock)');
    }
};
