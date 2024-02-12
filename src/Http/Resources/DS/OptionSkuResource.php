<?php

namespace Fpaipl\Brandy\Http\Resources\DS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OptionSkuResource extends JsonResource
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
            "name" => $this->name,
            "slug" => $this->slug,
            "sid" => $this->sid,
            "image" => $this->image,
        ];
    }
}
