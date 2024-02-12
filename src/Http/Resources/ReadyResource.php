<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Resources\ChatResource;
use Fpaipl\Brandy\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\ReadyItemResource;

class ReadyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $itemsResources = LedgerItemsResource::collection($this->readyItems)->toArray($request);
        $groupedItems = collect($itemsResources)->groupBy('groupId')->map(function ($group) {
            $firstItem = $group->first();
            $totalQuantity = $group->sum('quantity');
            return [
                'quantity' => $totalQuantity,
                'option' => $firstItem['option'],
                'image' => $firstItem['image'],
                'preview' => $firstItem['preview'],
                'ranges' => $group->map(function ($item) {
                    return [
                        'range' => $item['range'],
                        'quantity' => $item['quantity'],
                        'mrp' => $item['mrp'],
                        'rate' => $item['rate'],
                    ];
                })->values(),
            ];
        });

        return [
            'id' => $this->id,
            'sid' => $this->sid,
            'ledger_id' => $this->ledger_id,
            'quantity' => $this->quantity,
            'user' => new UserResource($this->user),
            'note' =>  ChatResource::collection($this->chats),
            'items' => $groupedItems,
        ];
    }
}
