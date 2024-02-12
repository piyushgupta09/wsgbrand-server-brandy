<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Resources\PartyResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\ChatResource;
use Fpaipl\Brandy\Http\Resources\StockLedgerItemResource;

class StockLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [          
            "sid" =>  $this->sid,
            "party" =>  new PartyResource($this->party),
            "balance_qty" =>  $this->balance_qty,
            "demandable_qty" =>  $this->demandable_qty,
            'chats' => ChatResource::collection($this->chats),
            'records' => new StockLedgerItemResource([
                $this->orders, 
                $this->demands, 
                $this->readies, 
                $this->adjustments
            ]),
        ];
    }
}
