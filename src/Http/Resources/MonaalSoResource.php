<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\MaterialSoItemResource;

class MonaalSoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'wsg_po_id' => $this->id,
            'm_customer_id' => $this->m_customer_id,
            'm_customer_name' => $this->m_customer_name, 
            'm_product_id' => $this->m_product_id, 
            'm_order_id' => $this->m_order_id, 
            'm_catelog_id' => $this->m_catelog_id,
            'status' => $this->status,
            'accepted_at' => $this->accepted_at,
            'order_quantity' => $this->order_quantity,
            'items' => MaterialSoItemResource::collection($this->poItems),
        ];
    }
}
