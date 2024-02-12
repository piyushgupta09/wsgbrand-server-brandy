<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Exception;
use Fpaipl\Brandy\Util;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Models\Dispatch;
use Illuminate\Support\Facades\DB;
use Fpaipl\Brandy\Models\DispatchItem;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\LedgerResource;
use Fpaipl\Brandy\Http\Resources\DispatchResource;
use Fpaipl\Brandy\Http\Requests\DispatchCreateRequest;

class DispatchCoordinator extends Coordinator 
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        // Determine the role for the scope
        $role = $user->isBrand() ? 'brand' : ($user->isVendor() ? 'vendor' : ($user->isFactory() ? 'factory' : null));
        if (!$role) {
            return ApiResponse::error('Invalid request', 422);
        }

        $perPage = $request->perpage ?? 20;
        $search = $request->search ?? null;
        $status = $request->status ?? null;
        $sortBy = $request->sortby ?? 'created_at';
        $sortOrder = $request->sortorder ?? 'desc';

        if ($request->party) {
            $selectedParty = Party::where('sid', $request->party)->first();
            $partyId = $selectedParty ? $selectedParty->id : null;
        } else {
            $partyId = null;
        }

        switch ($role) {
            case 'brand':
                $dispatches = Dispatch::brandDispatches($user->id)->filteredDispatches($role, $status, $search, $sortBy, $sortOrder, $partyId)
                    ->paginate($perPage);
                break;
            
            case 'vendor':
                $dispatches = Dispatch::partyDispatches($user->party->id)->filteredDispatches($role, $status, $search, $sortBy, $sortOrder)
                    ->paginate($perPage);
                break;

            case 'factory':
                $dispatches = Dispatch::partyDispatches($user->party->id)->filteredDispatches($role, $status, $search, $sortBy, $sortOrder)
                    ->paginate($perPage);
                break;

            default:
                $dispatches = collect([]);
                break;
        }

        return ApiResponse::success([
            'data' => DispatchResource::collection($dispatches),
            'pagination' => [
                'total' => $dispatches->total(),
                'perPage' => $dispatches->perPage(),
                'currentPage' => $dispatches->currentPage(),
                'lastPage' => $dispatches->lastPage(),
            ],
        ]);

    }

    public function store(DispatchCreateRequest $request)
    {
        DB::beginTransaction();

        try {

            // Compute the total dispatch quantity from the request data
            // by parsing the json string and adding all the quantities
            $dispatchQuantityTotal = Util::calculateQuantity($request->quantities);

            // Get the ledger from the request
            $ledger = Ledger::where('sid', $request->ledger_sid)->first();
            
            // Check if the ready quantity is greater than the balance quantity
            if($dispatchQuantityTotal > $ledger->getNetDispatchableQty()){
                throw new Exception('Dispatch quantity can not exceed ' . $ledger->getNetDispatchableQty() . '.');
            }

            // Create the ready and adjust ledger (via Ready Boot Method)
            $dispatch = Dispatch::create([
                'ledger_id' => $ledger->id,
                'party_id' => $ledger->party->id,
                'quantity' => $dispatchQuantityTotal,
                'expected_at' => $request->expected_at,
                'user_id' => auth()->user()->id,
                'tags' => $ledger->tags,
            ]);

            DispatchItem::createDispatchItems($dispatch, $ledger, json_decode($request->quantities, true));
            Chat::createChatIfNecessary($request, $dispatch);
            DB::commit();

            return ApiResponse::success(new LedgerResource($dispatch->ledger));
        } catch(\Exception $e){
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}
