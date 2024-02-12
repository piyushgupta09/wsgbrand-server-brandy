<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Fpaipl\Brandy\Util;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Brandy\Models\Ledger;
use Illuminate\Support\Facades\DB;
use Fpaipl\Brandy\Models\Adjustment;
use Fpaipl\Brandy\Models\AdjustmentItem;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\LedgerResource;
use Fpaipl\Brandy\Http\Requests\AdjustmentRequest;

class AdjustmentCoordinator extends Coordinator
{
    public function store(AdjustmentRequest $request)
    {
        DB::beginTransaction();

        try {

            // Compute the total dispatch quantity from the request data
            // by parsing the json string and adding all the quantities
            $adjustmentQuantityTotal = Util::calculateQuantity($request->quantities);

            // Get the ledger from the request
            $ledger = Ledger::where('sid', $request->ledger_sid)->first();
                        
            // Create the ready and adjust ledger (via Ready Boot Method)
            $adjustment = Adjustment::create([
                'type' => $request->type,
                'ledger_id' => $ledger->id,
                'quantity' => $adjustmentQuantityTotal,
                'user_id' => auth()->user()->id,
            ]);

            // Validate the adjustment quantity
            switch ($request->type) {
                case 'order':
                    $ledger->last_activity = 'Order';
                    if ($ledger->getNetReadyableQty() < $adjustmentQuantityTotal) {
                        throw new \Exception('Max adjustment quantity is ' . $ledger->getNetReadyableQty());
                    }
                    $ledger->order_adj = $ledger->order_adj + $adjustmentQuantityTotal;
                    break;
                case 'ready':
                    $ledger->last_activity = 'Ready';
                    if ($ledger->getNetDemandableQty() < $adjustmentQuantityTotal) {
                        throw new \Exception('Max adjustment quantity is ' . $ledger->getNetDemandableQty());
                    }
                    $ledger->ready_adj = $ledger->ready_adj + $adjustmentQuantityTotal;
                    break;
                case 'demand':
                    $ledger->last_activity = 'Demand';
                    if ($ledger->getNetDispatchableQty() < $adjustmentQuantityTotal) {
                        throw new \Exception('Max adjustment quantity is ' . $ledger->getNetDispatchableQty());
                    }
                    $ledger->demand_adj = $ledger->demand_adj + $adjustmentQuantityTotal;
                    default: break;
            }

            // Update the ledger
            $ledger->save();

            // Create the adjustment items
            AdjustmentItem::createAdjustmentItems($adjustment, $ledger, json_decode($request->quantities, true));
            Chat::createChatIfNecessary($request, $adjustment);
            DB::commit();
            
            return ApiResponse::success(new LedgerResource($adjustment->ledger));

        } catch(\Exception $e){
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 404);
        }

    }
}
