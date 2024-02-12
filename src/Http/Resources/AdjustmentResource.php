<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Resources\ChatResource;
use Fpaipl\Brandy\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\AdjustmentItemResource;

class AdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => new UserResource($this->user),
            'ledger_id' => $this->ledger_id,
            'quantity' => $this->quantity,
            'type' => $this->type,
            'note' =>  ChatResource::collection($this->chats),
            'created_at' => $this->created_at,
            'adjustmentItems' => AdjustmentItemResource::collection($this->adjustmentItems),
        ];
    }
}
