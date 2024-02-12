<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\LedgerItemStockResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);

        return [
            // "id" => $this->id,
            "stock" => new LedgerItemStockResource($this->stock),
            // "order_id" => $this->order_id,
            "quantity" => $this->quantity,
        ];
    }
}
