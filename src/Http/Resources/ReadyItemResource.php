<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\LedgerItemStockResource;

class ReadyItemResource extends JsonResource
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
            "id" => $this->id,
            "stock_id" => new LedgerItemStockResource($this->stock),
            "ready_id " => $this->ready_id,
            "quantity" => $this->quantity,
        ];
    }
}
