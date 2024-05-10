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
    
            // // Use optional object to safely access foreign key IDs
            'brand' => optional($this->brand)->wsg_id,
            'category' => optional($this->category)->wsg_id,
            'tax' => optional($this->tax)->wsg_id,
    
            // // Check if 'stock' relationship is loaded and if it exists
            'in_stock' => optional($this->whenLoaded('stock'))->quantity ? true : false,
            'stocks' => $this->whenLoaded('stock', function () {
                return $this->stock->stockItems->map(function ($stockItem) {
                    return [
                        'sid' => $stockItem->sid,
                        'sku' => $stockItem->sku,
                        'in_stock' => $stockItem->quantity > 0,
                        'product_name' => $stockItem->product_name,
                        'product_sid' => $stockItem->product_sid,
                        'product_code' => $stockItem->product_code,
                        'product_option_sid' => $stockItem->product_option_sid,
                        'product_range_sid' => $stockItem->product_range_sid,
                        'active' => $stockItem->active,
                    ];
                });
            }, []), // Return an empty array if not loaded
    
            'product_collections' => $this->productCollections,
            'product_options' => ProductOptionResource::collection($this->whenLoaded('productOptions')->sortBy('id')),
            'product_ranges' => ProductRangeResource::collection($this->whenLoaded('productRanges')->sortBy('id')),
            'product_attributes' => ProductAttributeResource::collection($this->whenLoaded('productAttributes')->sortBy('id')),
            'product_measurements' => ProductMeasurementResource::collection($this->whenLoaded('productMeasurements')->sortBy('id')),
        ];
    }
}
