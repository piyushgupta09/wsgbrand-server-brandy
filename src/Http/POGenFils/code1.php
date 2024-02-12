<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Carbon\Carbon;
use Fpaipl\Brandy\Util;
use Fpaipl\Brandy\Models\Po;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Brandy\Models\Order;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Models\PoItem;
use Illuminate\Support\Facades\DB;
use Fpaipl\Brandy\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Brandy\Http\Resources\OrderResource;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\LedgerResource;
use Fpaipl\Brandy\Http\Requests\OrderUpdateRequest;

class OrderCoordinator extends Coordinator
{
    // public function index(Request $request)
    // {
    //     /** @var User $user */
    //     $user = auth()->user();

    //     // Determine the role for the scope
    //     $role = $user->isManagerBrand() ? 'brand' : ($user->isManagerVendor() ? 'vendor' : ($user->isManagerFactory() ? 'factory' : null));
    //     if (!$role) {
    //         return ApiResponse::error('Invalid request', 422);
    //     }

    //     // Determine the query ID for the scope
    //     $queryId = $role == 'brand' ? $user->id : $user->party->id;

    //     // Determine the status for the scope
    //     $status = $request->status ?? ($role == 'brand' ? 'rejected' : 'issued');

    //     // If the status is 'issued', then we need to check if the order is accepted by the vendor
    //     if ($status == 'issued') {
    //         $status = $role == 'vendor' ? 'accepted' : $status;
    //     }

    //     // Determine the search for the scope
    //     $search = $request->search ?? null;

    //     if ($user->isParty()) {
    //         $orders = Order::partyOrders($user->party->id)
    //             ->filteredOrders($queryId, $status, $search, $role)
    //             ->with('chats', 'chats.user', 'orderItems', 'po', 'po.poItems')
    //             ->paginate(10);
    //     } else {
    //         $orders = Order::brandOrders()
    //             ->filteredOrders($queryId, $status, $search, $role)
    //             ->with('chats', 'chats.user', 'orderItems', 'po', 'po.poItems')
    //             ->paginate(10);
    //     }

    //     return ApiResponse::success([
    //         'data' => OrderResource::collection($orders),
    //         'pagination' => [
    //             'total' => $orders->total(),
    //             'perPage' => $orders->perPage(),
    //             'currentPage' => $orders->currentPage(),
    //             'lastPage' => $orders->lastPage(),
    //         ],
    //     ]);
 
    //     // /** @var User $user */
    //     // $user = auth()->user();

    //     // // Determine the role for the scope
    //     // $role = $user->isManagerBrand() ? 'brand' : ($user->isManagerVendor() ? 'vendor' : ($user->isManagerFactory() ? 'factory' : null));
    //     // if (!$role) {
    //     //     return ApiResponse::error('Invalid request', 422);
    //     // }

    //     // // Determine the query ID for the scope
    //     // $queryId = $role == 'brand' ? $user->id : $user->party->id;

    //     // // Determine the status for the scope
    //     // $status = $request->status ?? ($role == 'brand' ? null : 'issued');

    //     // // If the status is 'issued', then we need to check if the order is accepted by the vendor
    //     // if ($status == 'issued') {
    //     //     $status = $role == 'vendor' ? 'accepted' : $status;
    //     // }

    //     // // Determine the search for the scope
    //     // $search = $request->search ?? null;

    //     // Fetch the orders
    //     // $orders = Order::filteredOrders(
    //     //     $queryId, $status, $search, $role
    //     // )->with('chats', 'chats.user', 'orderItems', 'po', 'po.poItems')->get();

    //     // Return the response
    //     // return ApiResponse::success(OrderResource::collection($orders));
    // }

    public function index(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        // Determine the role for the scope
        $role = $user->isBrand() ? 'brand' : ($user->isVendor() ? 'vendor' : ($user->isFactory() ? 'factory' : null));
        if (!$role) {
            return ApiResponse::error('Invalid request', 422);
        }

        $search = $request->search ?? null;
        $status = $request->status ?? null;

        switch ($role) {
            case 'brand':
                $orders = Order::brandOrders($user->id)
                    ->filteredOrders($role, $status, $search)
                    ->with('chats', 'chats.user', 'orderItems')
                    ->paginate(20);
                break;
            
            case 'vendor':
                $orders = Order::partyOrders($user->party->id)
                    ->filteredOrders($role, $status, $search)
                    ->with('chats', 'chats.user', 'orderItems')
                    ->paginate(20);
                break;

            case 'factory':
                $orders = Order::partyOrders($user->party->id)
                    ->filteredOrders($role, $status, $search)
                    ->with('chats', 'chats.user', 'orderItems')
                    ->paginate(20);
                break;

            default:
                $orders = collect([]);
                break;
        }
    
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

    // public function factoryIndex(Request $request)
    // {
    //     /** @var User $user */
    //     $user = auth()->user();

    //     // Determine the role for the scope
    //     if (!$user->isFactory()) {
    //         return ApiResponse::error('Invalid request', 422);
    //     }

    
    //     // Determine the search for the scope
    //     $search = $request->search ?? null;
    //     $status = $request->status ?? null;
        
    //     $orders = Order::partyOrders($user->party->id)
    //         ->filteredOrders('factory', $status, $search)
    //         ->with('chats', 'chats.user', 'orderItems', 'po', 'po.poItems')
    //         ->paginate(20);

    //     return ApiResponse::success([
    //         'data' => OrderResource::collection($orders),
    //         'pagination' => [
    //             'total' => $orders->total(),
    //             'perPage' => $orders->perPage(),
    //             'currentPage' => $orders->currentPage(),
    //             'lastPage' => $orders->lastPage(),
    //         ],
    //     ]);
 
    //     // /** @var User $user */
    //     // $user = auth()->user();

    //     // // Determine the role for the scope
    //     // $role = $user->isManagerBrand() ? 'brand' : ($user->isManagerVendor() ? 'vendor' : ($user->isManagerFactory() ? 'factory' : null));
    //     // if (!$role) {
    //     //     return ApiResponse::error('Invalid request', 422);
    //     // }

    //     // // Determine the query ID for the scope
    //     // $queryId = $role == 'brand' ? $user->id : $user->party->id;

    //     // // Determine the status for the scope
    //     // $status = $request->status ?? ($role == 'brand' ? null : 'issued');

    //     // // If the status is 'issued', then we need to check if the order is accepted by the vendor
    //     // if ($status == 'issued') {
    //     //     $status = $role == 'vendor' ? 'accepted' : $status;
    //     // }

    //     // // Determine the search for the scope
    //     // $search = $request->search ?? null;

    //     // Fetch the orders
    //     // $orders = Order::filteredOrders(
    //     //     $queryId, $status, $search, $role
    //     // )->with('chats', 'chats.user', 'orderItems', 'po', 'po.poItems')->get();

    //     // Return the response
    //     // return ApiResponse::success(OrderResource::collection($orders));
    // }

    public function show(Request $request, Order $order)
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isStaff()) {
            if ($order->user_id != auth()->user()->id) {
                return ApiResponse::error('Invalid request', 422);
            }
        } else if ($user->isFabricator()) {
            if ($order->ledger->party_id != auth()->user()->party->id) {
                return ApiResponse::error('Invalid request', 422);
            }
        }
        if (env('APP_DEBUG')) {
            Cache::forget('order' . $order);
        }

        $order = Cache::remember('order' . $order, Order::getCacheRemember(), function () use ($order) {
            return $order;
        });


        return ApiResponse::success(new OrderResource($order));
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string|max:255',
            'ledger_sid' => 'required|exists:ledgers,sid',
            'quantities' => ['required', 'string'],
            'expected_at' => [
                'sometimes', 
                'date', 
                'after_or_equal:today', 
                'before_or_equal:today + 1 month', 
            ],
        ]);

        DB::beginTransaction();

        try {

            $ledger = Ledger::where('sid', $request->ledger_sid)->first();

            if (!$ledger) {
                return ApiResponse::error('Invalid request', 422);
            }

            $order = Order::create([
                'ledger_id' => $ledger->id,
                'user_id' => auth()->user()->id,
                'party_id' => $ledger->party->id,
                'expected_at' => $request->expected_at,
                'quantity' => Util::calculateQuantity($request->quantities),
                'log_status_time' => Util::updateStatusLog(new Order(), Order::STATUS[0]),
            ]);

            OrderItem::createOrderItems($order, $ledger, json_decode($request->quantities, true));
            Chat::createChatIfNecessary($request, $order);
            DB::commit();
            
            return ApiResponse::success(new LedgerResource($order->ledger));

        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
            
        }
    }

    public function update(OrderUpdateRequest $request, Order $order)
    {
        $user = auth()->user();

        if (!$this->isValidStatusTransition($order->status, $request->status, $user)) {
            return ApiResponse::error('Invalid request', 422);
        }

        try {
            if ($this->isOrderBeingAccepted($order, $request, $user)) {
                $this->handleOrderAcceptance($order, $request);
            } else {
                //  if status is deleted then delete the order, but check order should not be accepted
                if ($request->status == Order::STATUS[5]) {
                    if ($order->status !== Order::STATUS[0]) {
                        return ApiResponse::error('Order can not be deleted', 422);
                    }
                    $order->delete();
                }
                $order->status = $request->status;
                $order->save();
            }
            
            Chat::createChatIfNecessary($request, $order);
            return ApiResponse::success(new OrderResource($order));

        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 404);
        }
    }

    private function isValidStatusTransition($currentStatus, $newStatus, $user)
    {
        // Manager can update any status
        if ($user->isManagerBrand()) {
            return true;
        }
    
        // Fabricator can accept (issue to accept) or reject (issue to reject) an order
        if ($user->isManagerVendor() || $user->isManagerFactory()) {
            return ($currentStatus == Order::STATUS[0] && 
                    ($newStatus == Order::STATUS[1] || $newStatus == Order::STATUS[3]));
        }
    
        // Staff can re-issue a rejected order
        if ($user->isManagerBrand()) {
            return ($currentStatus == Order::STATUS[3] && $newStatus == Order::STATUS[0]);
        }
    
        // Other status transitions are not allowed
        return false;
    }

    private function isOrderBeingAccepted($order, $request, $user)
    {
        return $order->status == Order::STATUS[0] && $request->status == Order::STATUS[1] && ($user->isManagerVendor() || $user->isManagerFactory());
    }

    private function handleOrderAcceptance($order, $request)
    {
        $createdAt = Carbon::parse($order->created_at);
        $twentyFourHoursLater = $createdAt->copy()->addHours(24);
        $currentDate = Carbon::now();
    
        if ($currentDate->gt($twentyFourHoursLater)) {
            return ApiResponse::error('You can not accept the order after 24 hours of order created.', 422);
        }
    
        $order->status = Order::STATUS[1];
        $order->queued = 0;
        $order->log_status_time = Order::setLog(Order::STATUS[1], $order);
        $order->update();

        $productMaterials = $order->ledger->product->productMaterials;

        foreach ($productMaterials as $productMaterial) {

            $material = $productMaterial->material;

            // 1. Check if the material supplier is Monaal Creation
            if ($material->supplier_id != config('monaal.supplier_id')) {
                continue;
            }

            // 2. Create a PO for the material
            $po = Po::create([
                'order_id' => $order->id,
                'name' => $material->name,
                'product_id' => $order->ledger->product_id,
                'party_id' => $order->ledger->party->id,
                'material_id' => $material->id,
                'm_customer_id' => $order->ledger->party->sid,
                'm_customer_name' => $order->ledger->party->business,
                'm_product_id' => Po::removeSupplierPrefix($material->sid),
                'm_order_id' => $order->sid,
                'm_catelog_id' => $order->ledger->product->code,
                'status' => Po::STATUS[0],
                'accepted_at' => Carbon::now(),
                'order_quantity' => $order->quantity,
            ]);         

            // Initialize a collection to hold your processed PoItem data
            $poItemsData = collect();

            // Loop thru each order item
            foreach ($order->orderItems as $orderItem) {

                // If order is of Tshirt of red color in small size, then
                $orderProductOptionId = $orderItem->stockItem->productOption->id; // Red color id of tshirt
                $orderProductRangeId = $orderItem->stockItem->productRange->id; // Small size id of tshirt

                // Material ke kon-kon-se color chahiye red color ke tshirt banane ke liye
                $materialOptions = $orderItem->stockItem->productOption->pomos->pluck('product_option_id', 'material_option_id');
                $selectedFields = $orderItem->stockItem->productRange->pomrs
                        ->groupBy('product_material_id')
                        ->map(function ($group) {
                            // From each group, take the first item only
                            $firstItem = $group->first();
                            // Return only the desired fields from the first item
                            return collect($firstItem->only(['cost', 'quantity', 'product_material_id', 'material_range_id','product_range_id']));
                        });
                    

                $newArr = array();

                foreach ($materialOptions as $materialOptionId => $productOptionId) {
                    
                }

                foreach ($selectedFields as $materialId => $selectedField) {
                    if ($materialId == $material->id) {
                        # code...
                    }
                }
                
                Log::info(json_encode([
                    'order_id' => $order->id,
                    'order_product_option_id' => $orderProductOptionId,
                    'order_product_range_id' => $orderProductRangeId,
                    'material_id' => $material->id,
                    'material_options' => $materialOptions,
                    'selected_fields' => collect($selectedFields),
                ]));


                // foreach ($orderItem->stockItem->productOption->pomos as $pomo) {
                    
                //     // This is the id of material color i need to make the tshirt
                //     $materialOptionId = $pomo->material_option_id; // cottom-red
                //     $materialProductOptionId = $pomo->product_option_id; // red

                
                // }
                
                // This is the id of material size i need to make the tshirt  
                // $materialRange = $orderItem->stockItem->productRange->pomrs->first(); // cotton-panna-56
                // $materialRangeId = $materialRange->material_range_id; 
                // $materialProductRangeId = $materialRange->product_range_id;

                // Perform the calculation for the PoItem
                // $fcpu = $materialRange->quantity;
                // $rate = $materialRange->cost;
                // $quantity = $orderItem->quantity * $fcpu;
                // $amount = $quantity * $rate;

                // Log::info([
                //     'po_id' => $po->id,
                //     'name' => $orderItem->stockItem->productOption->name . ' ' . $orderItem->stockItem->productRange->name,
                //     'material_option_id' => $materialOptionId,
                //     'material_range_id' => $materialRangeId,
                //     'product_option_id' => $materialProductOptionId,
                //     'product_range_id' => $materialProductRangeId,
                //     'order_quantity' => $orderItem->quantity,
                //     'fcpu' => $fcpu,
                //     'rate' => $rate,
                //     'quantity' => $quantity,
                //     'amount' => $amount,
                // ]);
                                
            }

            // Noe i can create the PoItem
            // PoItem::updateOrCreate($itemData);
        }
    }

    public function destroy(Order $order)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isManager()) {
            return ApiResponse::error('Invalid request', 422);
        } else if ($order->status != Order::STATUS[4]) {
            return ApiResponse::error('Order can not be deleted', 422);
        }

        try {
            $order->delete();
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 404);
        }

        return ApiResponse::success('Order deleted successfully', 200);
    }   
}
