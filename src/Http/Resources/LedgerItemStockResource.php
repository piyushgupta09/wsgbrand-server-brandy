<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Fetchers\DsFetcher;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerItemStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);

        $dsFetcherObj = new DsFetcher();
        $params = '?'.$dsFetcherObj->api_secret();
        $response = $dsFetcherObj->makeApiRequest('get', '/api/products/'.$this->product_sid, $params);
        $product = $response->data;

        return [
            // "id" => $this->id,
            // "sku" => $this->sku,
            "name" => $product->name,
            "option_name" => $this->product_option_name,
            "quantity" => $this->quantity,
            'image' => $this->getFirstImage($product->options, $this->product_option_id, 'image'),
            'preview' => $this->getFirstImage($product->options, $this->product_option_id, 'preview'),

            "product_sid" => $this->product_sid,
            "product_id" => $this->product_id,
            "product_option_id" => $this->product_option_id,
            "product_option_sid" => $this->product_option_sid,
            "product_range_id" => $this->product_range_id,
            "product_range_sid" => $this->product_range_sid,
            "active" => $this->active,
            "note" => $this->note,
        ];
    }

    /**
     * Optionally, implement this function to fetch the first image
     * You could fetch it from a relation or some other way.
     */
    private function getFirstImage($options, $optionId, $name): string
    {
        foreach($options as $option){
            if($option->id == $optionId){
                return $option->$name;
            }

        }
        // Your implementation here
        //return collect($options)->first()->image;
    }
}
