<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductAttributeResource extends JsonResource
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
            'attrikey_id' => $this->attrikey_id,
            'attrival_id' => $this->attrival_id,
            'name' => $this->attrikey->name,
            'value' => $this->attrival->value,
        ];
    }
}
