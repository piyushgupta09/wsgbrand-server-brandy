<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionResource extends JsonResource
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
            'sid' => $this->sid,
            'name' => $this->name,
            'image' => $this->image,
            'images' => collect($this->image),
       ];
    }
}