<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Exception;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Stock;
use Illuminate\Support\Facades\DB;
use Fpaipl\Brandy\Http\Fetchers\DsFetcher;
use Fpaipl\Brandy\Http\Requests\StockRequest;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Brandy\Http\Resources\StockResource;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\ProductResource;
use Fpaipl\Brandy\Http\Resources\StockSkuResource;
use Fpaipl\Brandy\Http\Resources\ShowProductResource;
use Fpaipl\Panel\Events\ReloadDataEvent;

class StockCoordinator extends Coordinator
{
    public function brandIndex(Request $request)
    {
        // First check the request type. i.e. Productwise or Skuwise
        if ($request->has('type') && $request->type == 'sku') {
            // Skuwise
            $stocks = Stock::all();
            $stockResponse = StockSkuResource::collection($stocks);
        } else {
            // Productwise
            $stocks = Stock::select('product_sid', DB::raw('SUM(quantity) as quantity'), 'active')->groupBy('product_sid', 'active')->get();
            $stockResponse = StockResource::collection($stocks);
        }

        return ApiResponse::success($stockResponse);

    }

    public function fabIndex(Request $request)
    {
        $partyId = null;

        /** @var User $user */
        $user = auth()->user();
        if($user->hasRole('fabricator')){
            $partyId = $user->party->id;
        }

        $latestOrderEachLedger = DB::table('orders')
            ->join('ledgers', 'orders.ledger_id', '=', 'ledgers.id')
            ->select(
                'orders.*',
                'ledgers.sid as ledger_sid',
                'ledgers.last_activity',
                'ledgers.balance_qty',
                'ledgers.readyable_qty',
                'ledgers.demandable_qty',
                'ledgers.dispatchable_qty',
                'ledgers.total_order',
                'ledgers.total_ready',
                'ledgers.total_demand',
                'ledgers.total_dispatch',
                'ledgers.product_sid',
                DB::raw('(SELECT SUM(quantity) FROM orders WHERE ledger_id = ledgers.id AND status = "accepted") as total_order_qty'),
                DB::raw('(SELECT COALESCE(SUM(quantity), 0) FROM readies WHERE ledger_id = ledgers.id) as total_ready_qty'),
                DB::raw('(SELECT COALESCE(SUM(quantity), 0) FROM demands WHERE ledger_id = ledgers.id) as total_demand_qty')
            )
            ->where(function($query) use ($partyId) {
                if($partyId != null){
                    $query->where('ledgers.party_id', $partyId);
                }
            })
            ->whereIn('orders.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('orders')
                    ->groupBy('ledger_id');
            })
            ->orderBy('orders.id', 'desc')
            ->paginate(2); 

        $stockResponse = [];
    
        foreach($latestOrderEachLedger as $order){

            $dsFetcherObj = new DsFetcher();
            $params = '?'.$dsFetcherObj->api_secret();
            $response = $dsFetcherObj->makeApiRequest('get', '/api/products/'.$order->product_sid, $params);
            $product = $response->data;

            $stock = Stock::where('product_sid', $order->product_sid)->where('active', true)->first();

            array_push($stockResponse, [
                'ledger_sid' => $order->ledger_sid,
                'last_activity' => $order->last_activity,
                'balance_qty' => $order->balance_qty,
                'readyable_qty' => $order->readyable_qty,
                'demandable_qty' => $order->demandable_qty,
                'dispatchable_qty' => $order->dispatchable_qty,
                'total_order' => $order->total_order,
                'total_ready' => $order->total_ready,
                'total_demand' => $order->total_demand,
                'total_dispatch' => $order->total_dispatch,
                'product_code' => $stock->product_code,
                'product' => new ProductResource($product),
            ]);
        }

        return ApiResponse::success([
            'data' => $stockResponse,
            'pagination' => [
                'total' => $latestOrderEachLedger->total(),
                'perPage' => $latestOrderEachLedger->perPage(),
                'currentPage' => $latestOrderEachLedger->currentPage(),
                'lastPage' => $latestOrderEachLedger->lastPage(),
            ],
        ]);
    }

    public function store(StockRequest $request)
    {
        DB::beginTransaction();

        try {

            // $options = '[{"id": 1},{"id": 2}]';
            // $ranges = '[{"id": 1},{"id": 2},{"id": 3}]';
            // make an api call to ds to fetch the product along with options and ranges

            $dsFetcherObj = new DsFetcher();
            $params = $request->product_sid . '?' . $dsFetcherObj->api_secret();
            $response = $dsFetcherObj->makeApiRequest('get', '/api/products/', $params);
            if ($response->statusCode == 200 && $response->status == config('api.ok')) {
                $product = $response->data;
                $productId = $product->id;
            } else {
                throw new Exception('DS:Server Error, Try again later');
            }

            $productOptions = $product->options;
            $productRanges = $product->ranges;

            foreach ($productOptions as $option) {
                $productOptionId = $option->id;
                $productOptionSid = $option->sid;
                foreach ($productRanges as $range) {
                    $productRangeId = $range->id;
                    $productRangeSid = $range->sid;
                    $skuId = $productId . "-" . $productOptionId . "-" . $productRangeId;
                    $stock = Stock::where('sku', $skuId)->withTrashed()->first();
                    if ($stock) {
                        if ($stock->trashed()) {
                            $stock->restore();
                        }
                    } else {
                        Stock::create([
                            'sku' => $skuId,
                            'product_id' => $productId,
                            'product_sid' => $request->product_sid,
                            'product_option_id' => $productOptionId,
                            'product_option_sid' => $productOptionSid,
                            'product_range_id' => $productRangeId,
                            'product_range_sid' => $productRangeSid,
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 404);
        }
        return ApiResponse::success('Stock created successfully.');
    }

    /**
     * here we consider given id to be a product_sid,
     * Make stock Active or Inactive by product wise
     */
    public function update(Request $request, Stock $stock)
    {
        $request->validate([
            'qtype' => 'required|in:toggle,delete',
            'value' => 'required_if:qtype,toggle|boolean'
        ]);

        try {
            switch ($request->qtype) {
                case 'toggle':
                    $result = $stock->skus()->update(['active' => $request->value]);
                    break;

                case 'delete':
                    // We can not delete stock if quantity of any sku of a product is greater than 0
                    // even if any one result is available then we can not delete any on the related also
                    $stockHasQty = $stock->skus->sum('quantity') > 0;

                    if ($stockHasQty) {
                        throw new Exception('You can not delete product that has stock.');
                    } else {
                        $stock->skus()->delete();
                        $result = 'Record has been deleted successfully.';
                    }
                    break;

                default: break;
            }

            ReloadDataEvent::dispatch(Stock::UPDATE_EVENT . '#' . $stock->product_sid);
            return ApiResponse::success(new ShowProductResource($stock));
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 404);
        }

        // return ApiResponse::success([
        //     $request->query_type => $result
        // ]);
    }

    // public function update(Request $request, Stock $stock)
    // {
    //     $request->validate([
    //         'query_type' => 'required|in:toggle_active',
    //         'value' => 'required|boolean'
    //     ]);

    //     try {
    //         switch ($request->query_type) {
    //             case 'toggle_active':
    //                 $result = $stock->skus()->update(['active' => $request->value]);
    //                 break;

    //             default:
    //                 break;
    //         }
    //     } catch (\Exception $e) {
    //         return ApiResponse::error($e->getMessage(), 404);
    //     }

    //     return ApiResponse::success([
    //         $request->query_type => $result
    //     ]);
    // }

    /**
     * here we are not showing stock for given id,
     * instead we consider given id to be a product_sid,
     * and return all stock total for that product_sid
     */
    public function show(Request $request, Stock $stock)
    {
        // $stock = Stock::groupBy('product_sid', 'active')->selectRaw('product_sid , active, sum(quantity) as quantity')->where('product_sid', $stock->product_sid)->first();
        return ApiResponse::success(new ShowProductResource($stock));
    }

    public function query(StockRequest $request)
    {
        $request->validate([
            'query_type' => 'required|in:exists,stock,stock_sku,sku_count',
            'sku_id' => 'required_if:query_type,stock_sku|exists:stocks,sku'
        ]);

        $sid = null;
        $result = null;

        try {
            switch ($request->query_type) {
                case 'exists':
                    $result = Stock::where('product_sid', $request->product_sid)->exists();
                    break;

                case 'stock_sku':
                    $stock = Stock::where('product_sid', $request->product_sid)->where('sku', $request->sku_id)->firstOrFail();
                    $sid = $stock->product_option_sid;
                    $result = $stock->quantity;
                    break;

                case 'stock':
                    $result = Stock::where('product_sid', $request->product_sid)->sum('quantity');
                    break;

                case 'sku_count':
                    $result = Stock::where('product_sid', $request->product_sid)->count();
                    break;

                default:
                    break;
            }
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 404);
        }

        return ApiResponse::success([
            'sid' => $sid ?? null,
            $request->query_type => $result
        ]);
    }

    // public function delete(StockRequest $request)
    // {
    //     // We can not delete stock if quantity of any sku of a product is greater than 0
    //     try {
    //         // even if any one result is available then we can not delete any on the related also
    //         $stockHasQty = Stock::where('product_sid', $request->product_sid)->sum('quantity') > 0;

    //         if ($stockHasQty) {
    //             throw new Exception('You can not delete product that has stock.');
    //         } else {
    //             // delete all stock sku's
    //             Stock::where('product_sid', $request->product_sid)->delete();
    //         }
    //     } catch (\Exception $e) {
    //         return ApiResponse::error($e->getMessage(), 404);
    //     }
    //     return ApiResponse::success('Record has been deleted successfully.');
    // }
}
