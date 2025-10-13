<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Branch;

class ProductBranchStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * توزيع المنتجات على الفروع الثلاثة مع كميات أولية
     */
    public function run(): void
    {
        $products = Product::all();
        $branches = Branch::all();

        $stocks = [];
        
        foreach ($products as $product) {
            foreach ($branches as $branch) {
                // توزيع عشوائي للمخزون بين الفروع
                $stock = rand(10, 100);
                
                // بعض المنتجات في بعض الفروع قد تكون بكميات منخفضة
                if (rand(1, 5) == 1) {
                    $stock = rand(0, 5);
                }
                
                $stocks[] = [
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'current_stock' => $stock,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('product_branch_stock')->insert($stocks);
    }
}
