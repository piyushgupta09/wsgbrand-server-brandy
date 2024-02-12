<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Http\Fetchers\DsFetcher;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\ProductResource;
use Fpaipl\Brandy\Http\Resources\StockLedgerItemResource;

class StockResource extends JsonResource
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

        /** @var User $user */
        $user = auth()->user();
        $newOrder = false;
        if ($user->isFabricator()) {
            $ledger = Ledger::where('product_sid', $product->sid)->where('party_id', $user->party->id)->first();
            if(!empty($ledger)){
                $quantity = $ledger->balance_qty;
                $ledgerItemCollections = new StockLedgerItemResource([$ledger->orders, $ledger->readies, $ledger->demands, $ledger->ledgerAdjustments]);
                $ledgerItem_arr = json_decode(($ledgerItemCollections->toJson()),true);
                $lst_ledgerItem = end($ledgerItem_arr);
                if(strtolower($lst_ledgerItem['model']) == 'order'){
                    $newOrder = true;
                }
            }
        } else {
            $quantity = $this->quantity;
        }

        return [
            'stock' => $quantity,
            'newOrder' => $newOrder,
            'active' => $this->active?true:false,
            'product' => new ProductResource($product),
            'tags' => $product->tags,
            'assigned_parties' => $this->partiesLedger()->map(function ($partyLedger) {
                return [
                    'sid' => $partyLedger->party->sid,
                    'name' => $partyLedger->party->user->name,
                ];
            }),
        ];
    }
}