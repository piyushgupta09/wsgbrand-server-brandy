<?php

namespace Fpaipl\Brandy\Http\Resources\DS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\DS\RangeSkuResource;
use Fpaipl\Brandy\Http\Resources\DS\OptionSkuResource;

class ProductSkuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);

        $product_arr = explode("-", $request->sku);

        return [
            'name' => $this->name,
            'sid' => $this->sid,
            'start_price' => $this->start_price,
            'end_price' => $this->end_price,
            'price' => $this->start_price,
            'moq' => $this->moq,
            'hsncode' => $this->hsncode,
            'gstrate' => $this->gstrate,
            'options'=> new OptionSkuResource($this->options),
            "ranges" => new RangeSkuResource($this->ranges),
        ];
    }
}
