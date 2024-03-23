<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Exception;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Party;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Brandy\Http\Resources\PartyResource;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Requests\PartyCreateRequest;
use Fpaipl\Brandy\Http\Requests\PartyUpdateRequest;

class PartyCoordinator extends Coordinator
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role' => ['sometimes','string',Rule::in(Party::TYPE)],
            'product_sid' => ['sometimes','string','exists:stocks,product_sid'],
            'status' => ['sometimes','string',Rule::in(['active', 'inactive'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cacheKey = 'parties' . $request->query('role', '') . $request->query('product_sid', '') . $request->query('status', '');

        // if (env('APP_DEBUG')) {
            Cache::forget($cacheKey);
        // }
        
        $parties = Cache::remember($cacheKey, config('api.cache.duration'), function () use ($request) {
            if ($request->has('role') || $request->has('status')) {
                return Party::getParty($request->query('role'), $request->query('status'))
                            ->orderBy('created_at', 'desc')
                            ->get();
            } else {
                return Party::orderBy('created_at', 'desc')->get();
            }
        });

        if ($request->has('product_sid')) {
            // Exclude parties that have a ledger with the specified product_sid
            $parties = $parties->reject(function ($party) use ($request) {
                return $party->ledgers->contains('product_sid', $request->product_sid);
            });
        }
    
        return ApiResponse::success(PartyResource::collection($parties));
    }

    public function store(PartyCreateRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();

        // Only  manager can create party
        if (!$user->isManager()) {
            return ApiResponse::error('Invalid request', 422);
        }
       
        DB::beginTransaction();

        try{

            $exist = Party::where('user_id', $request->user_id)->first();

            if($exist){
                throw new Exception('Party is already created of this user.');
            }

            $party = Party::create([
                'user_id' => $request->user_id,
                'business' => $request->business,
                'gst' => $request->gst,
                'pan' => $request->pan,
                'sid' => $request->sid,
                'type' => $request->type,
                'info' => $request->info,
            ]);

            if($party){

                $party->user->assignRole($party->type);
                // $party->manageTag($request->validated());
                if($request->image){
                   // Log::info('image');
                    $party->addSingleMediaToModal($request->image);
                }
            }

            DB::commit();

        } catch(\Exception $e){
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), 404);
        }

        return ApiResponse::success(new PartyResource($party));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Party $party)
    {
        if (env('APP_DEBUG')) {
            Cache::forget('party' . $party);
        }
       
        $party = Cache::remember('party' . $party, config('api.cache.remember'), function () use ($party) {
            return $party;
        });
        
        return ApiResponse::success(new PartyResource($party));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PartyUpdateRequest $request, Party $party)
    {
        /** @var User $user */
        $user = auth()->user();

        // Only  manager can update party
        if (!$user->isManager()) {
            return ApiResponse::error('Invalid request', 422);
        }
        try{
            if($request->active == 'true'){
                $active = 1;
            } else {
                $active = 0;
            }
            $party->active = $active;
            $party->save();
        } catch(\Exception $e){
            return ApiResponse::error($e->getMessage(), 404);
        }
        return ApiResponse::success('Party updated successfully');
    }

    /**
    * Remove the specified resource from storage.
    */
    public function destroy(Party $party)
    {
        /** @var User $user */
        $user = auth()->user();
        
        // Only  manager can delete party
        if (!$user->isManager()) {
            return ApiResponse::error('Invalid request', 422);
        }

        try{
            $party->delete();
        } catch(\Exception $e){
            return ApiResponse::error($e->getMessage(), 404);
        }

        return ApiResponse::success(null,'Record has been deleted.');
    }
}
