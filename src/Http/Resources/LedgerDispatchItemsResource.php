<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Prody\Models\ProductOption;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerDispatchItemsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // check its dispatch has purchaseItem or not
        // if yes then show purchaseItem quantity in billedQuantity
        // else show quantity in billedQuantity
        $is_billed = $this->purchaseItems->isNotEmpty();
        $billedQuantity = $is_billed ? $this->purchaseItems->sum('quantity') : $this->quantity;

        $tolerance = $this->dispatch->ledger->demands->last()->tolerance ?? 10;
        $productOption = $this->stockItem->productOption;
        $productRange = $this->stockItem->productRange;

        return [
            'groupId' => $productOption->id,
            'option' => $productOption->name,
            'option_sid' => $productOption->slug,
            'range_sid' => $productRange->slug,
            'image' => $productOption->getImage(ProductOption::MEDIA_CONVERSION_THUMB),
            'preview' => $productOption->getImage(ProductOption::MEDIA_CONVERSION_PREVIEW),
            'range' => $productRange->name,
            'mrp' => $productRange->mrp,
            'rate' => $productRange->rate,
            'is_billed' => $is_billed,
            'quantity' => $this->quantity,
            'billed_qty' => $is_billed ? $billedQuantity : $this->quantity,
            'diffrence' => $billedQuantity - $this->quantity,
            'max_quantity' => round($this->quantity * (1 + $tolerance / 100), 0),
            'min_quantity' => max(1, round($this->quantity * (1 - $tolerance / 100), 0)),            
            'tolerance' => $tolerance,
        ];
    }
}
