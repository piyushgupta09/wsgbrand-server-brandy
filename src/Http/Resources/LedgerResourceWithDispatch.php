<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Resources\ChatResource;
use Fpaipl\Brandy\Http\Resources\PartyResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Prody\Http\Resources\BrandProductsResource;
use Fpaipl\Brandy\Http\Resources\LedgerItemsLedgerResource;

class LedgerResourceWithDispatch extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // "id" =>  $this->id,
            "sid" =>  $this->sid,
            "name" =>  $this->name,
            "product_sid" =>  $this->product_sid,
            "product_code" =>  $this->product->code,
            "product_id" =>  $this->product_id,
            "party_id" =>  new PartyResource($this->party),
            'last_activity' => $this->last_activity,
            'unaccepted' => $this->getUnacceptedOrders(),
            'balance_qty' => $this->getLedgerBalanceQty(),
            'readyable_qty' => $this->getNetReadyableQty(),
            'demandable_qty' => $this->getNetDemandableQty(),
            'dispatchable_qty' => $this->getNetDispatchableQty(),
            'stockable_qty' => $this->getNetStockableQty(),
            'total_order' => $this->total_order - $this->order_adj,
            'total_ready' => $this->total_ready - $this->ready_adj,
            'total_demand' => $this->total_demand - $this->demand_adj,
            'total_dispatch' => $this->total_dispatch,
            'items' => new LedgerItemsLedgerResource([
                $this->orders()->nonRejected()->get(), 
                $this->readies, 
                $this->demands, 
                $this->adjustments,
                $this->dispatches
            ]),
            'chats' => ChatResource::collection($this->chats->sortByDesc('created_at')->take(8)->reverse()),
            'product' => new BrandProductsResource($this->product),
        ];
    }
}
