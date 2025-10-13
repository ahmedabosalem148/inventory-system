<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'unit' => $this->unit,
            'pack_size' => $this->pack_size,
            'purchase_price' => (float) $this->purchase_price,
            'sale_price' => (float) $this->sale_price,
            'min_stock' => $this->min_stock,
            'reorder_level' => $this->reorder_level,
            'is_active' => $this->is_active,
            
            // Category information
            'category' => [
                'id' => $this->category_id,
                'name' => $this->whenLoaded('category', fn() => $this->category->name),
            ],
            
            // Stock information
            'total_stock' => $this->when(
                $this->relationLoaded('branchStocks'),
                fn() => $this->branchStocks->sum('current_stock')
            ),
            
            'branch_stocks' => $this->whenLoaded('branchStocks', function () {
                return $this->branchStocks->map(function ($stock) {
                    return [
                        'branch_id' => $stock->branch_id,
                        'branch_name' => $stock->branch?->name,
                        'current_stock' => $stock->current_stock,
                        'is_low_stock' => $stock->current_stock < $this->min_stock,
                    ];
                });
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
