<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\Branch;

class AddInitialStock extends Command
{
    protected $signature = 'stock:add-initial {--quantity=100}';
    protected $description = 'Add initial stock for all products in all branches';

    public function handle()
    {
        $quantity = (int) $this->option('quantity');
        
        $products = Product::all();
        $branches = Branch::all();
        
        $this->info("Adding initial stock of {$quantity} for each product in each branch...");
        
        $added = 0;
        
        foreach ($products as $product) {
            foreach ($branches as $branch) {
                // Check if stock record exists
                $existingStock = ProductBranchStock::where('product_id', $product->id)
                    ->where('branch_id', $branch->id)
                    ->first();
                
                if (!$existingStock) {
                    ProductBranchStock::create([
                        'product_id' => $product->id,
                        'branch_id' => $branch->id,
                        'current_stock' => $quantity,
                    ]);
                    $added++;
                    $this->line("✅ Added {$quantity} units of '{$product->name}' to branch '{$branch->name}'");
                } else {
                    $this->line("ℹ️  Stock already exists for '{$product->name}' in '{$branch->name}' (Current: {$existingStock->current_stock})");
                }
            }
        }
        
        $this->info("✅ Completed! Added stock records for {$added} product-branch combinations.");
        
        return Command::SUCCESS;
    }
}