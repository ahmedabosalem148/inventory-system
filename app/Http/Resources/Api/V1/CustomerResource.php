<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'phone' => $this->phone,
            'address' => $this->address,
            'tax_id' => $this->tax_id,
            'credit_limit' => (float) $this->credit_limit,
            'balance' => (float) $this->balance,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            
            // Balance status
            'balance_status' => $this->balance > 0 ? 'له' : ($this->balance < 0 ? 'عليه' : 'متساوي'),
            
            // Ledger entries (when loaded)
            'ledger_entries' => $this->whenLoaded('ledgerEntries', function () {
                return CustomerLedgerEntryResource::collection($this->ledgerEntries);
            }),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
