<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Prody\Models\ProductOption;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerItemsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $productOption = $this->stockItem->productOption;
        $productRange = $this->stockItem->productRange;

        return [
            "groupId" => $productOption->id,
            "quantity" => $this->quantity,
            "option" => $productOption->name,
            "image" => $productOption->getImage(ProductOption::MEDIA_CONVERSION_THUMB),
            "preview" => $productOption->getImage(ProductOption::MEDIA_CONVERSION_PREVIEW),
            "range" => $productRange->name,
            "mrp" => $productRange->mrp,
            "rate" => $productRange->rate,
        ];
    }
}
