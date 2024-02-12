<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Prody\Models\ProductOption;
use Fpaipl\Brandy\Http\Resources\ChatResource;
use Fpaipl\Brandy\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Prody\Http\Resources\BrandProductsResource;
use Fpaipl\Brandy\Http\Resources\LedgerDispatchItemsResource;

class DispatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $itemsResources = LedgerDispatchItemsResource::collection($this->dispatchItems)->toArray($request);
        $groupedItems = collect($itemsResources)->groupBy('groupId')->map(function ($group) {
            $firstItem = $group->first();
            $totalQuantity = $group->sum('quantity');
            $totalDiffrence = $group->sum('diffrence');
            $totalBilledQuantity = $group->sum('billed_qty');
            $totalMinQuantity = $group->sum('min_quantity');
            $totalMaxQuantity = $group->sum('max_quantity');
            
            return [
                'is_billed' => $firstItem['is_billed'],
                'quantity' => $totalQuantity,
                'min_quantity' => $totalMinQuantity,
                'max_quantity' => $totalMaxQuantity,
                'billed_qty' => $totalBilledQuantity,
                'diffrence' => $totalDiffrence,
                'option' => $firstItem['option'],
                'image' => $firstItem['image'],
                'preview' => $firstItem['preview'],
                'option_sid' => $firstItem['option_sid'],
                'ranges' => $group->map(function ($item) {
                    return [
                        'range' => $item['range'],
                        'range_sid' => $item['range_sid'],
                        'mrp' => $item['mrp'],
                        'rate' => $item['rate'],
                        'quantity' => $item['quantity'],
                        'diffrence' => $item['diffrence'],
                        'max_quantity' => $item['max_quantity'],
                        'min_quantity' => $item['min_quantity'],
                        'tolerance' => $item['tolerance'],
                        'billed_qty' => $item['billed_qty'],
                    ];
                })->values(),
            ];
        });

        return [
            'sid' => $this->sid,
            'product_name' => $this->ledger->product->name,
            'product_code' => $this->ledger->product->code,
            'name' => $this->ledger->name,
            'ledger_sid' => $this->ledger->sid,
            'quantity' => $this->quantity,
            'status' => $this->purchases->isNotEmpty() ? 'received' : 'pending',
            'party' => [
                'sid' => $this->party->sid,
                'name' => $this->party->business,
                'mobile' => $this->party->mobile,
                'type' => $this->party->type,
                'image' => $this->party->getImage(Party::MEDIA_CONVERSION_THUMB),
            ],
            'note' => ChatResource::collection($this->chats->merge($this->purchases->flatMap(function($purchase) {
                return $purchase->chats;
            }))),
            'image' => $this->ledger->getImage(ProductOption::MEDIA_CONVERSION_THUMB),
            'preview' => $this->ledger->getImage(ProductOption::MEDIA_CONVERSION_PREVIEW),
            'items' => $groupedItems,
            // 'items' => DispatchItemResource::collection($this->dispatchItems),
            'tags' => $this->tags,
        ];
    }
}
