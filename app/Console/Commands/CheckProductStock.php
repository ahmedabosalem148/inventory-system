<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\Branch;

class CheckProductStock extends Command
{
    protected $signature = 'stock:check {product_id?} {--add=0}';
    protected $description = 'Check stock for a specific product and optionally add stock';

    public function handle()
    {
        $productId = $this->argument('product_id');
        $addStock = (int) $this->option('add');
        
        if (!$productId) {
            // Show all products with their stock
            $this->info('All products stock status:');
            $this->table(
                ['ID', 'Product Name', 'Branch', 'Current Stock'],
                $this->getAllProductsStock()
            );
            return Command::SUCCESS;
        }
        
        $product = Product::find($productId);
        if (!$product) {
            $this->error("Product with ID {$productId} not found!");
            return Command::FAILURE;
        }
        
        $this->info("Stock for product: {$product->name} (ID: {$product->id})");
        
        $branches = Branch::all();
        $stockData = [];
        
        foreach ($branches as $branch) {
            $stock = ProductBranchStock::where('product_id', $product->id)
                ->where('branch_id', $branch->id)
                ->first();
            
            $currentStock = $stock ? $stock->current_stock : 0;
            $stockData[] = [$branch->name, $currentStock];
            
            if ($addStock > 0) {
                if ($stock) {
                    $stock->update(['current_stock' => $stock->current_stock + $addStock]);
                    $this->info("✅ Added {$addStock} units to '{$branch->name}'. New total: " . ($currentStock + $addStock));
                } else {
                    ProductBranchStock::create([
                        'product_id' => $product->id,
                        'branch_id' => $branch->id,
                        'current_stock' => $addStock,
                    ]);
                    $this->info("✅ Created stock record for '{$branch->name}' with {$addStock} units");
                }
            }
        }
        
        if ($addStock == 0) {
            $this->table(['Branch', 'Current Stock'], $stockData);
        }
        
        return Command::SUCCESS;
    }
    
    private function getAllProductsStock()
    {
        $data = [];
        $products = Product::with('branchStocks.branch')->get();
        
        foreach ($products as $product) {
            if ($product->branchStocks->isEmpty()) {
                $data[] = [$product->id, $product->name, 'No branches', 0];
            } else {
                foreach ($product->branchStocks as $stock) {
                    $data[] = [
                        $product->id,
                        $product->name,
                        $stock->branch->name ?? 'Unknown',
                        $stock->current_stock
                    ];
                }
            }
        }
        
        return $data;
    }
}