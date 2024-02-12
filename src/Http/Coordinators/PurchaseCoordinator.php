<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Exception;
use Carbon\Carbon;
use Fpaipl\Brandy\Util;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Brandy\Models\Stock;
use Fpaipl\Brandy\Models\Chatable;
use Fpaipl\Brandy\Models\Dispatch;
use Fpaipl\Brandy\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Fpaipl\Brandy\Models\PurchaseItem;
use Fpaipl\Panel\Events\ReloadDataEvent;
use Fpaipl\Brandy\Http\Fetchers\DsFetcher;
use Fpaipl\Brandy\Models\PurchaseDispatch;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\DispatchResource;
use Fpaipl\Brandy\Http\Resources\PurchaseResource;
use Fpaipl\Brandy\Http\Requests\PurchaseCreateRequest;

class PurchaseCoordinator extends Coordinator
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
                $purchases = Purchase::brandPurchases($user->id)->filteredPurchases($role, $status, $search, $sortBy, $sortOrder, $partyId)
                    ->paginate($perPage);
                break;

            case 'vendor':
                $purchases = Purchase::partyPurchases($user->party->id)->filteredPurchases($role, $status, $search, $sortBy, $sortOrder)
                    ->paginate($perPage);
                break;

            case 'factory':
                $purchases = Purchase::partyPurchases($user->party->id)->filteredPurchases($role, $status, $search, $sortBy, $sortOrder)
                    ->paginate($perPage);
                break;

            default:
                $purchases = collect([]);
                break;
        }

        return ApiResponse::success([
            'data' => PurchaseResource::collection($purchases),
            'pagination' => [
                'total' => $purchases->total(),
                'perPage' => $purchases->perPage(),
                'currentPage' => $purchases->currentPage(),
                'lastPage' => $purchases->lastPage(),
            ],
        ]);
    }

    public function store(PurchaseCreateRequest $request)
    {
        $dispatch = Dispatch::where('sid', $request->dispatch_sid)->firstOrFail();
        $ledger = $dispatch->ledger;

        DB::beginTransaction();

        try {

            // Compute the total recevied quantity from the request data
            // by parsing the json string and adding all the quantities
            $receviedQuantityTotal = Util::calculateQuantity($request->quantities, 'int');

            // Get the ledger from the request

            // Check if the recevied quantity is greater than the balance quantity
            // if($receviedQuantityTotal > $ledger->balance_qty){
            //     throw new Exception('Recevied quantity can not exceed ' . $ledger->balance_qty . '.');
            // }

            $purchase = Purchase::firstOrCreate(
                [
                    'party_id' => $ledger->party_id,
                    'doc_id' => $request->doc_id,
                ],
                [
                    'doc_date' => $request->doc_date,
                    'status' => 'received',
                ]
            );

            $quantitiesArr = json_decode($request->quantities, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid quantities JSON');
            }

            if (!is_array($quantitiesArr)) {
                throw new Exception('Invalid quantities Array');
            }

            PurchaseItem::createPurchaseItems($purchase, $dispatch, $quantitiesArr);
            Chat::createChatIfNecessary($request, $purchase);
            DB::commit();

            ReloadDataEvent::dispatch(Purchase::NEW_PURCHASE_EVENT);

            // send back latest dispatched
            $dispatches = Dispatch::brandDispatches()->paginate(10);
            return ApiResponse::success([
                'data' => DispatchResource::collection($dispatches),
                'pagination' => [
                    'total' => $dispatches->total(),
                    'perPage' => $dispatches->perPage(),
                    'currentPage' => $dispatches->currentPage(),
                    'lastPage' => $dispatches->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 404);
        }
    }
}
