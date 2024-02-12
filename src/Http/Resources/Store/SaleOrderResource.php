<?php

namespace Fpaipl\Brandy\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleOrderResource extends JsonResource
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
            "id" => $this->id,
            "sid" => $this->sid,
            "quantity" => $this->quantity,
            "customer_id" => $this->customer_id, //Fabricator
            "customer_sid" => $this->customer_sid,
            "stock_id" => $this->stock_id,
            "job_work_order_sid" => $this->job_work_order_sid,
            "variation" => $this->variation,
            "rate" => $this->rate,
            "payment_terms" => $this->payment_terms,
            "delivery_terms" => $this->delivery_terms,
            "quality_terms" => $this->quality_terms,
            "general_terms" => $this->general_terms,
            "pre_order" => $this->pre_order ? 'pre order' : 'normal',
            "approved" => $this->approved ? 'approved' : 'unapproved',
            "accepter" => $this->accepter,
            "accepted_at" => $this->accepted_at,
            "pending" => $this->pending ? 'pending' : 'completed',
            'items' => $this->items
        ];
    }
}
