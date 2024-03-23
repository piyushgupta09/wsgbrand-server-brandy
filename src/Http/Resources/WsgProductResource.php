<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Prody\Http\Resources\ProductRangeResource;
use Fpaipl\Prody\Http\Resources\ProductOptionResource;
use Fpaipl\Prody\Http\Resources\ProductAttributeResource;
use Fpaipl\Prody\Http\Resources\ProductMeasurementResource;

class WsgProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'sid' => $this->sid,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'code' => $this->code,
            'details' => $this->details,
            'mrp' => $this->mrp,
            'rate' => $this->rate,
            'moq' => $this->moq,
            'active' => $this->active,
            'tags' => $this->tags,

            'brand' => $this->brand->wsg_id,
            'category' => $this->category->wsg_id,
            'tax' => $this->tax->wsg_id,

            'in_stock' => $this->stock->quantity ? true : false,
            'stocks' => $this->stock->stockItems->map(function ($stockItem) {
                return [
                    'sid' => $stockItem->sid,
                    'sku' => $stockItem->sku,
                    'in_stock' => $stockItem->quantity ? true : false,
                    'product_name' => $stockItem->product_name,
                    'product_sid' => $stockItem->product_sid,
                    'product_code' => $stockItem->product_code,
                    'product_option_sid' => $stockItem->product_option_sid,
                    'product_range_sid' => $stockItem->product_range_sid,
                    'active' => $stockItem->active,
                ];
            }),
            'product_collections' => $this->productCollections,
            'product_options' => ProductOptionResource::collection($this->whenLoaded('productOptions')->sortBy('id')),
            'product_ranges' => ProductRangeResource::collection($this->whenLoaded('productRanges')->sortBy('id')),
            'product_attributes' => ProductAttributeResource::collection($this->whenLoaded('productAttributes')->sortBy('id')),
            'product_measurements' => ProductMeasurementResource::collection($this->whenLoaded('productMeasurements')->sortBy('id')),
        ];
    }
}
