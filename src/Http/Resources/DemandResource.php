<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Resources\ChatResource;
use Fpaipl\Brandy\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\DemandItemResource;

class DemandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);

        return [
            'id' => $this->id,
            'sid' => $this->sid,
            'ledger_id' => $this->ledger_id,
            'quantity' => $this->quantity,
            'expected_at' => $this->expected_at,
            'status' => $this->status,
            'user' => new UserResource($this->user),
            'note' =>  ChatResource::collection($this->chats),
            'items' => DemandItemResource::collection($this->demandItems),
        ];
    }
}
