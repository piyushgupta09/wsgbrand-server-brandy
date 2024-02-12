<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Exception;
use Fpaipl\Brandy\Util;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Brandy\Models\Demand;
use Fpaipl\Brandy\Models\Ledger;
use Illuminate\Support\Facades\DB;
use Fpaipl\Brandy\Models\DemandItem;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\LedgerResource;
use Fpaipl\Brandy\Http\Requests\DemandCreateRequest;

class DemandCoordinator extends Coordinator
{
    public function store(DemandCreateRequest $request)
    {
        DB::beginTransaction();

        try {

            // Compute the total demand quantity from the request data
            // by parsing the json string and adding all the quantities
            $demandQuantityTotal = Util::calculateQuantity($request->quantities);

            // Get the ledger from the request
            $ledger = Ledger::where('sid', $request->ledger_sid)->first();
            
            // Check if the ready quantity is greater than the balance quantity
            if($demandQuantityTotal > $ledger->getNetDemandableQty()){
                throw new Exception('Demand quantity can not exceed ' . $ledger->getNetDemandableQty() . '.');
            }

            // Create the ready and adjust ledger (via Ready Boot Method)
            $demand = Demand::create([
                'ledger_id' => $ledger->id,
                'quantity' => $demandQuantityTotal,
                'expected_at' => $request->expected_at,
                'user_id' => auth()->user()->id,
                'tolerance' => $request->tolerance ?? 0,
            ]);

            DemandItem::createDemandItems($demand, $ledger, json_decode($request->quantities, true));
            Chat::createChatIfNecessary($request, $demand);
            DB::commit();
            return ApiResponse::success(new LedgerResource($demand->ledger));
        } catch(\Exception $e){
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 404);
        }
    }
}
