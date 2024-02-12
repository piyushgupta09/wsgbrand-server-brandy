<?php

namespace Fpaipl\Brandy\Http\Resources;

use Fpaipl\Brandy\Models\Party;
use Illuminate\Http\Request;
use Fpaipl\Prody\Models\ProductOption;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $groupedPurchaseItems = [];
    
        foreach ($this->purchaseItems as $purchaseItem) {
            $groupId = $purchaseItem->group_id;
            $productOption = $purchaseItem->stockItem->productOption;

            if (!isset($groupedPurchaseItems[$groupId])) {
                $groupedPurchaseItems[$groupId] = [
                    'groupId' => $groupId,
                    'quantity' => 0,
                    'amount' => 0,
                    'product_name' => $purchaseItem->stockItem->product->name,
                    'product_code' => $purchaseItem->stockItem->product->code,
                    'dispatched_on' => $purchaseItem->dispatchItem->created_at->format('d-m-Y'),
                    'image' => $productOption->getImage(ProductOption::MEDIA_CONVERSION_THUMB),
                    'preview' => $productOption->getImage(ProductOption::MEDIA_CONVERSION_PREVIEW),
                    'items' => []
                ];
            }
    
            $groupedPurchaseItems[$groupId]['items'][] = [
                'id' => $purchaseItem->id,
                'billed_quantity' => $purchaseItem->quantity,
                'sent_quantity' => $purchaseItem->dispatchItem->quantity,
                'sid' => $purchaseItem->stockItem->product->code,
                'option' => $productOption->name,
                'range' => $purchaseItem->stockItem->productRange->name,
                'rate' => $purchaseItem->rate,
                'amount' => $purchaseItem->amount,
            ];

            // Update total quantity and amount for the group
            $groupedPurchaseItems[$groupId]['quantity'] += $purchaseItem->quantity;
            $groupedPurchaseItems[$groupId]['amount'] += $purchaseItem->amount;
        }
    
        return [
            'doc_date' => $this->doc_date,
            'doc_id' => $this->doc_id,
            'quantity' => $this->quantity,
            'total' => $this->total,
            'groups' => array_values($groupedPurchaseItems),
            'tags' => $this->tags,
            'party' => [
                'sid' => $this->party->sid,
                'name' => $this->party->business,
                'mobile' => $this->party->mobile,
                'type' => $this->party->type,
                'image' => $this->party->getImage(Party::MEDIA_CONVERSION_THUMB),
                'preview' => $this->party->getImage(Party::MEDIA_CONVERSION_PREVIEW),
            ],
        ];
    }
    

}
