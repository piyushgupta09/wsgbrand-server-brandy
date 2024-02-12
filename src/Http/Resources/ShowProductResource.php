<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Order;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Http\Fetchers\DsFetcher;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\StockLedgerResource;
use Fpaipl\Brandy\Http\Resources\StockProductResource;

class ShowProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dsFetcherObj = new DsFetcher();
        $params = '?'.$dsFetcherObj->api_secret();
        $response = $dsFetcherObj->makeApiRequest('get', '/api/products/'. $this->product_sid, $params);
        $product = $response->data;
        /** @var User $user */
        $user = auth()->user();
        $quantity = 0;
        // $total_orders = 0;
        // $total_readies = 0;
        // $total_demands = 0;

        if ($user->isFabricator()) {
            $ledger = Ledger::where('product_sid', $product->sid)->where('party_id', $user->party->id)->first();
            if(!empty($ledger)){
                $quantity = $ledger->balance_qty;
            }
        } else {
            $quantity = $this->quantity;
        }

        // foreach($this->ledgers() as $ledger){
        //     $orders = $ledger->orders;
        //     foreach($orders as $order){
        //         if($order->status == Order::STATUS[1]){
        //             $total_orders += $order->quantity;
        //         }
        //     }

        //     $readies = $ledger->readies;
        //     foreach($readies as $ready){
        //         $total_readies += $ready->quantity;
        //     }

        //     $demands = $ledger->demands;
        //     foreach($demands as $demand){
        //         $total_demands += $demand->quantity;
        //     }
          
        // }

        // only if user isManager or isStaff
        if ($user->isManager() || $user->isStaff()) {
            $ledgerResource = StockLedgerResource::collection($this->ledgers());
        } else {
            $ledgerResource = [];
        }
       
        return [
            'stock' => $quantity,
            'code' => $this->product_code,
            'roq' => $this->roq,
            'incoming' => $this->incoming,
            'outgoing' => $this->outgoing,
            'active' => $this->active ? true : false,
            'product' => new StockProductResource($product),
            'ledgers' => $ledgerResource,
        ];
    }
}