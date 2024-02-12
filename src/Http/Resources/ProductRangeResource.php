<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductRangeResource extends JsonResource
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
            "sid" => $this->sid,
            "name" => $this->name,
            "mrp" => $this->name,
            "price" => $this->price,
       ];
    }
}