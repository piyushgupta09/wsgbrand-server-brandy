<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Http\Fetchers\DsFetcher;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\ProductResource;
use Fpaipl\Brandy\Http\Resources\StockLedgerItemResource;

class StockImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $dsFetcherObj = new DsFetcher();
        $params = '?'.$dsFetcherObj->api_secret();
        $response = $dsFetcherObj->makeApiRequest('get', '/api/products/'.$this->product_sid, $params);
        $product = $response->data;

        return [
            $product->options[0]->image,
        ];
    }
}