<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Resources\PoItemResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sid' => $this->sid,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'status' => $this->status,
            'uuid' => $this->sid, // change to uuid
            'name' => $this->name,
            'items' => PoItemResource::collection($this->poItems),
        ];
    }
}
