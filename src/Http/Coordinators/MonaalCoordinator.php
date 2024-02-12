<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use App\Models\User;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Order;
use Fpaipl\Brandy\Actions\LoadBills;
use Fpaipl\Brandy\Models\MonaalBill;
use Fpaipl\Brandy\Actions\SyncMonaalBills;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Brandy\Http\Resources\OrderResource;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\MonaalBillResource;

class MonaalCoordinator extends Coordinator
{
    public function poIndex(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        // Determine the role for the scope
        if (!$user->isFactory()) {
            return ApiResponse::error('Invalid request', 422);
        }
 
        $perPage = $request->perpage ?? 20;
        $search = $request->search ?? null;
        $status = $request->status ?? null;
        $sortBy = $request->sortby ?? 'created_at';
        $sortOrder = $request->sortorder ?? 'desc';

        $orders = Order::partyOrders($user->party->id)
            ->filteredOrders('factory', $status, $search)
            ->with('orderItems', 'pos', 'pos.poItems')
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
        
        return ApiResponse::success([
            'data' => OrderResource::collection($orders),
            'pagination' => [
                'total' => $orders->total(),
                'perPage' => $orders->perPage(),
                'currentPage' => $orders->currentPage(),
                'lastPage' => $orders->lastPage(),
            ],
        ]);
    }

    public function billIndex(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        // Determine the role for the scope
        if (!$user->isFactory()) {
            return ApiResponse::error('Invalid request', 422);
        }

        LoadBills::execute($user->party->sid);

        $search = $request->search ?? null;
        $status = $request->status ?? null;

        $monaalBills = MonaalBill::partyBills($user->party->sid)
            ->filteredBills($status, $search)
            ->paginate(20);
        
        return ApiResponse::success([
            'data' => MonaalBillResource::collection($monaalBills),
            'pagination' => [
                'total' => $monaalBills->total(),
                'perPage' => $monaalBills->perPage(),
                'currentPage' => $monaalBills->currentPage(),
                'lastPage' => $monaalBills->lastPage(),
            ],
        ]);
    }
   
}
