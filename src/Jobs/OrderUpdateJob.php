<?php

namespace Fpaipl\Brandy\Jobs;

use Carbon\Carbon;
use App\Models\User;
use Fpaipl\Brandy\Models\Po;
use Illuminate\Bus\Queueable;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Brandy\Models\Order;
use Fpaipl\Brandy\Models\Stock;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Models\Chatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Fpaipl\Panel\Events\ReloadDataEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Fpaipl\Brandy\Http\Fetchers\StoreFetcher;
use Fpaipl\Brandy\Models\PoItem;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Fpaipl\Brandy\Notifications\OrderUpdateNotification;

class OrderUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $order;
    private $data;
    private $request;

    public function __construct(Order $order, $request)
    {
        $this->order = $order;
        $this->data = [
            'customer_id' => auth()->user()->id, 
            'customer_sid' => auth()->user()->party->sid,
            'stock_id' => 1,
            'purchase_order_sid' => $order->sid,
            'quantities' => json_encode($this->createQuantities($order)),
            'order_id' => $order->id,
        ];
        $this->request = $request;
        Log::info($order, $request, $this->data);
    }

    public function handle(): void
    {
        DB::beginTransaction();

        try {
            // $storeFetcherrObj = new StoreFetcher();
            // $params = '?'.$storeFetcherrObj->api_secret();
            // $body = [
            //     // 'customer_id' => $this->data['customer_id'], 
            //     // 'customer_sid' => $this->data['customer_sid'],
            //     'customer_id' => 1, 
            //     'customer_sid' => 'C11',
            //     'stock_id' => $this->data['stock_id'],
            //     'purchase_order_sid' => $this->data['purchase_order_sid'],
            //     'quantities' => $this->data['quantities'],
            // ];

            // Log::info($body);

            // $response = $storeFetcherrObj->makeApiRequest('post', '/api/saleorders', $params, $body);
            
            // Log::info(print_r($response));

            // if($response->status == config('api.ok')) {

                $order = Order::findOrFail($this->data['order_id']);
                $order->status = Order::STATUS[1];
                $order->queued = 0;
                $order->log_status_time = Order::setLog(Order::STATUS[1], $order);
                $order->update();
                
                if(!empty($this->request['message'])){
                    Chat::createChat($this->request['message'], $order, $order->ledger->id);
                }

                $po = Po::create([
                    'order_id' => $order->id,
                    'party_id' => $order->ledger->party_id,
                    'status' => Po::STATUS[0],
                    'material_so_id' => 1,
                ]);
                
                PoItem::createPoItems($po, $order, $material_option_sid = 1);

            // } 

            DB::commit();
            ReloadDataEvent::dispatch(Order::UPDATE_ORDER_EVENT . '#' . $order->status);

        } catch(\Exception $e){

            DB::rollBack();
            Log::info($e);
            if(!empty($this->data['customer_id'])){
                $user = User::findOrFail($this->data['customer_id']);
                $user->notify(new OrderUpdateNotification($e->getMessage()));
            }
        }
    }

    private function createQuantities($order){
        $quantities = [];
        foreach($order->orderItems as $orderItem){
            $stock = Stock::findOrFail($orderItem->stock_id);
            $quantities[$stock->product_option_sid.'_'.$stock->product_range_sid] = $orderItem->quantity;
        }
        return $quantities;
    }
}
