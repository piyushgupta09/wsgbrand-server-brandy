<?php

namespace Fpaipl\Brandy\Http\Resources\DS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\DS\RangeResource;
use Fpaipl\Brandy\Http\Resources\DS\OptionResource;

class ProductResource extends JsonResource
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
            'id' =>$this->id,
            'sid' =>$this->sid,
            'name' => $this->name,
            'sid' => $this->sid,
            'start_price' => $this->start_price,
            'end_price' => $this->end_price,
            'price' => $this->start_price,
            'moq' => $this->moq,
            'hsncode' => $this->hsncode,
            'gstrate' => $this->gstrate,
            'styleid' => '#45957',
            'fabricator_id' => 1,
            'tags' => $this->tags,
            'options' => OptionResource::collection($this->options),
            'ranges' => RangeResource::collection($this->ranges),
        ];
    }
}