<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialSoItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_option_id' => $this->product_option_id,
            'product_range_id' => $this->product_range_id,
            'rate' => $this->rate,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'fcpu' => $this->fcpu,
            'order_quantity' => $this->order_quantity,
        ];
    }
}
