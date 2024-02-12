<?php

namespace Fpaipl\Brandy\Http\Resources\DS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\DS\RelatedSkuResource;

class SkuResource extends JsonResource
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
            'sku' => $this->sku,
            'name' => $this->name,
            'image' => $this->image,
            'related_skus' => RelatedSkuResource::collection($this->related_skus)
        ];
    }
}
