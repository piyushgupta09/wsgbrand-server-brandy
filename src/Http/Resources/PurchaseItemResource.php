<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Resources\StockResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\DispatchItemResource;

class PurchaseItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [            
            "stock" => new LedgerItemStockResource($this->stock),
            "sent_quantity" => $this->dispatchItem->quantity,
            "billed_quantity" => $this->quantity,
        ];
    }
}
