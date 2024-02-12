<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        /** @var User $activeUser */
        $activeUser = auth()->user();

        return [
            "id" => $this->id,
            "model" =>  Str::slug(Str::afterLast($this->chatable->chatable_type, '\\')),
            "content" => $this->content,
            'type' => $this->type ?? 'text',
            "myChat" => $this->sender_id === $activeUser->id,
            'ledger_id' => $this->ledger_id,
            "sender_name" => $this->user->name,
            "delivered_at" => $this->delivered_at,
            "recevied_at" => $this->recevied_at,
            "read_at" => $this->read_at,
            'items' => [],
        ];
    }
}
