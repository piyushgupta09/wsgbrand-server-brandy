<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonaalCustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // take only first 3 characters
        $limit = 3;
        $alias = Str::limit($this->user->name, $limit, '');

        return [
            'name' => $this->user->name,
            'business' => $this->business,
            'alias' => $alias,
            'pan' => $this->pan,
            'gstin' => $this->gstin,
            'note' => $this->info,
            'whatsapp' => $this->mobile,
            'contact' => $this->contact,
            'mobile' => $this->mobile,
            'email' => $this->user->email,
            'active' => $this->active,
            'slug' => Str::slug($this->business . '-' . $this->user->name),
            'sid' => $this->sid,
            'address' => [
                'line1' => $this->line1,
                'line2' => $this->line2,
                'state' => $this->state,
                'country' => $this->country,
                'pincode' => $this->pincode,
            ],
        ];
    }
}
