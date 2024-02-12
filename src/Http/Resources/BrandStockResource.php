<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\ProductResource;

class BrandStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'quantity' => $this->quantity,
            'active' => $this->active?true:false,
            'roq' => $this->roq,
            'incoming' => $this->incoming,
            'outgoing' => $this->outgoing,
            // 'product' => new ProductResource($this->product),
            // 'tags' => $this->product->tags,
            // 'assigned_parties' => $this->partiesLedger()->map(function ($partyLedger) {
            //     return [
            //         'sid' => $partyLedger->party->sid,
            //         'name' => $partyLedger->party->user->name,
            //     ];
            // }),
        ];
    }
}