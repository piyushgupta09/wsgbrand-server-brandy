<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductMeasurementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'measurekey_id' => $this->measurekey_id,
            'measureval_id' => $this->measureval_id,
            'name' => $this->measurekey->name,
            'value' => $this->measureval->value,
        ];
    }
}
