<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Brandy\Models\Ledger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Fpaipl\Panel\Events\ReloadDataEvent;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\LedgerResource;
use Fpaipl\Brandy\Http\Requests\LedgerShowRequest;
use Fpaipl\Brandy\Http\Requests\LedgerCreateRequest;
use Fpaipl\Brandy\Http\Resources\LedgerResourceWithDispatch;

/**
*  1. LedgerCoordinator
*     - show (GET /ledgers/{ledger})
*     - index (GET /ledgers)
*     - store (POST /ledgers)
*     - update (PUT /ledgers/{ledger})
*     - destroy (DELETE /ledgers/{ledger})
*/
class LedgerCoordinator extends Coordinator
{
    public function index(Request $request)
    {
        $cacheKey = 'ledgers_' . $request->product_sid . '_' . $request->party_sid;

        if (env('APP_DEBUG')) {
            Cache::forget($cacheKey);
        }

        $ledgers = Cache::remember($cacheKey, config('api.cache.duration'), function () use ($request) {
            /** @var User $user */
            $user = auth()->user();

            // Determine the party SID
            $partySid = $user->isFabricator() ? $user->party->sid /* Fabricator */ : $request->party_sid /* Staff */;

            return Ledger::filteredLedgers($request->product_sid, $partySid)->get();
        });

        return ApiResponse::success(LedgerResource::collection($ledgers));
    }

    public function store(LedgerCreateRequest $request)
    {
        // Cached during validation in LedgerCreateRequest
        $product = Cache::get($request->product_sid.date('dmy'));
        $party = Party::where('sid', $request->party_sid)->firstOrFail();

        // Check if the ledger already exists
        $ledgerExists = Ledger::where('product_id', $product->id)
                            ->where('party_id', $party->id)
                            ->exists();
        
        if ($ledgerExists) {
            return ApiResponse::error('Ledger already exists.', 422);
        }
        
        try {
            // Create the new ledger
            $ledger = Ledger::create([
                'product_id' => $product->id,
                'party_id' => $party->id,
                'name' => $product->name . "-" . $party->user->name,
                'product_sid' => $product->sid,
                'notes' => $request->notes ?? '',
            ]);

            ReloadDataEvent::dispatch(Ledger::NEW_LEDGER . '#' . $ledger->stock->product_sid);

            return ApiResponse::success(new LedgerResource($ledger));
        } catch (\Exception $e) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'product' => $product,
                    'party' => $party,
                    'request' => $request->all(),
                ])
                ->log($e->getMessage());
            return ApiResponse::error('An error occurred while creating the ledger.', 500);
        }
    }

    public function show(Ledger $ledger)
    {
        if ($ledger->party->user->isVendor()) {
            return ApiResponse::success(new LedgerResourceWithDispatch($ledger));
        }

        return ApiResponse::success(new LedgerResource($ledger));
    }

    public function showWithDispatches(Ledger $ledger)
    {
        return ApiResponse::success(new LedgerResourceWithDispatch($ledger));
    }

    public function update(Request $request, Ledger $ledger)
    {
        // Validate the request
        $data = $request->validate([
            'note' => 'required|string', // Adjust validation rules as needed
        ]);

        // Update the ledger's note
        $ledger->update([
            'note' => $data['note'],
        ]);

        return ApiResponse::success(new LedgerResource($ledger));
    }

    public function destroy(Ledger $ledger)
    {
        // Check if the ledger has any associated orders
        if ($ledger->orders()->exists()) {
            return ApiResponse::error('Ledger cannot be deleted as it has orders.', 422);
        }

        // Delete the ledger
        $ledger->delete();

        return ApiResponse::success(['message' => 'Ledger deleted successfully.']);
    }

    public function partyIndex(Request $request)
    {
       
        /** @var User $user */
        $user = auth()->user();

        $ledgers = Ledger::where('party_id', $user->party->id)->paginate(10);
        
        return ApiResponse::success([
            'data' => LedgerResource::collection($ledgers),
            'pagination' => [
                'total' => $ledgers->total(),
                'perPage' => $ledgers->perPage(),
                'currentPage' => $ledgers->currentPage(),
                'lastPage' => $ledgers->lastPage(),
            ],
        ]);
    }
}
