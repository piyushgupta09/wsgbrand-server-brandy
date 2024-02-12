<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Prody\Models\ProductOption;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\LedgerItemStockResource;

class DispatchItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // check its dispatch has purchaseItem or not
        // if yes then show purchaseItem quantity in new_quantity
        // else show quantity in new_quantity
        $new_quantity = $this->purchaseItems->isNotEmpty() ? $this->purchaseItems->sum('quantity') : $this->quantity;
        $tolerance = $this->dispatch->ledger->demands->last()->tolerance ?? 10;
        $productOption = $this->stockItem->productOption;

        return [
            "image" => $productOption->getImage(ProductOption::MEDIA_CONVERSION_THUMB),
            "preview" => $productOption->getImage(ProductOption::MEDIA_CONVERSION_PREVIEW),
            "option" => $productOption->name,
            "sent_quantity" => $this->quantity,
            "billed_quantity" => $new_quantity,
            'max_quantity' => round($this->quantity * (1 + $tolerance / 100), 2),
            'min_quantity' => round($this->quantity * (1 - $tolerance / 100), 2),
            'tolerance' => $tolerance,
        ];
    }
}
