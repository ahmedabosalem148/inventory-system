<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'location' => $this->location,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'is_main' => $this->is_main,
            
            // Stock summary (if loaded)
            'total_products' => $this->whenLoaded('productStocks', fn() => $this->productStocks->count()),
            'total_stock_value' => $this->when(
                $this->relationLoaded('productStocks'),
                fn() => $this->productStocks->sum(function ($stock) {
                    return $stock->current_stock * ($stock->product->purchase_price ?? 0);
                })
            ),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
