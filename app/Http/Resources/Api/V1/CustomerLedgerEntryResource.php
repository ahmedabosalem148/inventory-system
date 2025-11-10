<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerLedgerEntryResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'transaction_date' => $this->transaction_date,
            'transaction_type' => $this->transaction_type,
            'reference_number' => $this->reference_number,
            'reference_id' => $this->reference_id,
            'debit' => (float) ($this->debit ?? 0),
            'credit' => (float) ($this->credit ?? 0),
            'balance' => (float) ($this->balance ?? 0),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
