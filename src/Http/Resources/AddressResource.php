<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            "print" => $this->print,
            "fname" => $this->fname,
            "lname" => $this->lname,
            "contacts" => $this->contacts,
            "line1" => $this->line1,
            "line2" => $this->line2,
            "district" => $this->district,
            "state" => $this->state,
            "country" => $this->country,
            "pincode" => $this->pincode,
        ];
    }
}
