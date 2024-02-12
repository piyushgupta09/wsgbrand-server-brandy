<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Prody\Models\ProductOption;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPartyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "sid" => $this->sid,
            "username" => $this->user->name,
            "business" => $this->business,
            "type" => ucwords(str_replace('-', ' ', $this->type)),
            "tags" => $this->sid . ', ' . $this->business . ', ' . $this->gst . ', ' . $this->pan . ', ' . $this->type . ', ' . $this->info,
            'active' => $this->active,
            "image" => $this->getImage(Party::MEDIA_CONVERSION_THUMB),
            "preview" => $this->getImage(Party::MEDIA_CONVERSION_PREVIEW),
            'monaal_id' => $this->monaal_id,
            'info' => $this->info,
            'gstin' => $this->gstin,
            'pan' => $this->pan,
            'mobile' => $this->mobile,
            'contact' => $this->contact,
            'addresses' => [
                "billing" => [
                    'line1' => $this->line1,
                    'line2' => $this->line2,
                    'state' => $this->state,
                    'country' => $this->country,
                    'pincode' => $this->pincode,
                ],
                "shipping" => [
                    'line1' => $this->line1,
                    'line2' => $this->line2,
                    'state' => $this->state,
                    'country' => $this->country,
                    'pincode' => $this->pincode,
                ],
            ],
        ];
    }
}
