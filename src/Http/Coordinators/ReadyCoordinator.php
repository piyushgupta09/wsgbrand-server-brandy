<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Exception;
use Fpaipl\Brandy\Util;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Brandy\Models\Ready;
use Fpaipl\Brandy\Models\Ledger;
use Illuminate\Support\Facades\DB;
use Fpaipl\Brandy\Models\ReadyItem;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\LedgerResource;
use Fpaipl\Brandy\Http\Requests\ReadyCreateRequest;

class ReadyCoordinator extends Coordinator 
{
    public function store(ReadyCreateRequest $request)
    {
        DB::beginTransaction();

        try {

            // Compute the total ready quantity from the request data
            // by parsing the json string and adding all the quantities
            $readyQuantityTotal = Util::calculateQuantity($request->quantities);

            // Get the ledger from the request
            $ledger = Ledger::where('sid', $request->ledger_sid)->first();
            
            // Check if the ready quantity is greater than the balance quantity
            if($readyQuantityTotal > $ledger->getNetReadyableQty()){
                throw new Exception('Ready quantity can not exceed ' . $ledger->getNetReadyableQty() . '.');
            }

            // Create the ready and adjust ledger (via Ready Boot Method)
            $ready = Ready::create([
                'ledger_id' => $ledger->id,
                'quantity' => $readyQuantityTotal,
                'user_id' => auth()->user()->id,
            ]);

            ReadyItem::createReadyItems($ready, $ledger, json_decode($request->quantities, true));
            Chat::createChatIfNecessary($request, $ready);
            DB::commit();
            return ApiResponse::success(new LedgerResource($ready->ledger));
        } catch(\Exception $e){
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 404);
        }
    } 
}
