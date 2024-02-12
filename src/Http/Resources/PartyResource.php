<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Prody\Models\ProductOption;
use Fpaipl\Brandy\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PartyResource extends JsonResource
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
            "ledgers" => $this->ledgers,
            "tags" => $this->sid . ', ' . $this->business . ', ' . $this->gst . ', ' . $this->pan . ', ' . $this->type . ', ' . $this->info,
            'active' => $this->active,
            "image" => $this->getImage(Party::MEDIA_CONVERSION_THUMB),
            "preview" => $this->getImage(Party::MEDIA_CONVERSION_PREVIEW),
            "products" => $this->ledgers->map(function ($ledger) {
                return [
                    "name" => $ledger->product_sid . ' | ' . $ledger->name,
                    "image" => $ledger->getImage(ProductOption::MEDIA_CONVERSION_THUMB),
                    "preview" => $ledger->getImage(ProductOption::MEDIA_CONVERSION_PREVIEW),
                    "product_sid" => $ledger->product->slug,
                    "ledger_sid" => $ledger->sid,
                    "balance_qty" => $ledger->balance_qty,
                ];
            })->filter(),
            // "stats" => [
            //     "alloted" => [
            //         "title" => "Alloted Catelogs",
            //         "ledgers" => $this->ledgers->map(function ($ledger) {
            //             return [
            //                 "name" => $ledger->product_sid . ' | ' . $ledger->name,
            //                 "image" => $ledger->getImage(ProductOption::MEDIA_CONVERSION_THUMB),
            //                 "preview" => $ledger->getImage(ProductOption::MEDIA_CONVERSION_PREVIEW),
            //                 "product_sid" => $ledger->product_sid,
            //                 "ledger_sid" => $ledger->sid,
            //             ];
            //         })->filter(),
            //     ],
            //     "running" => [
            //         "title" => "Running Catelogs",
            //         "ledgers" => $this->ledgers->map(function ($ledger) {
            //             if ($ledger->balance_qty > 0) {
            //                 return [
            //                     "name" => $ledger->product_sid . ' | ' . $ledger->name,
            //                     "image" => $ledger->getImage(ProductOption::MEDIA_CONVERSION_THUMB),
            //                     "preview" => $ledger->getImage(ProductOption::MEDIA_CONVERSION_PREVIEW),
            //                     "product_sid" => $ledger->product_sid,
            //                     "ledger_sid" => $ledger->sid,
            //                 ];
            //             }
            //         })->filter(),
            //     ],
            //     "completed" => [
            //         "title" => "Completed Catelogs",
            //         "ledgers" => $this->ledgers->map(function ($ledger) {
            //             if ($ledger->balance_qty == 0) {
            //                 return [
            //                     "name" => $ledger->product_sid . ' | ' . $ledger->name,
            //                     "image" => $ledger->getImage(ProductOption::MEDIA_CONVERSION_THUMB),
            //                     "preview" => $ledger->getImage(ProductOption::MEDIA_CONVERSION_PREVIEW),
            //                     "product_sid" => $ledger->product_sid,
            //                     "ledger_sid" => $ledger->sid,
            //                 ];
            //             }
            //         })->filter(),
            //     ],
            // ],
        ];
    }
}
