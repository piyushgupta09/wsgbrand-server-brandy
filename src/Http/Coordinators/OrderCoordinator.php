<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Carbon\Carbon;
use Fpaipl\Brandy\Util;
use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Po;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Brandy\Models\Order;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Models\PoItem;
use Illuminate\Support\Facades\DB;
use Fpaipl\Brandy\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Fpaipl\Prody\Models\MaterialRange;
use Fpaipl\Prody\Models\MaterialOption;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Brandy\Http\Resources\OrderResource;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\LedgerResource;
use Fpaipl\Brandy\Http\Requests\OrderUpdateRequest;

class OrderCoordinator extends Coordinator
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
                $orders = Order::brandOrders($user->id)
                    ->filteredOrders($role, $status, $search, $partyId)
                    ->with('chats', 'chats.user', 'orderItems')->orderBy($sortBy, $sortOrder)
                    ->paginate($perPage);
                break;
            
            case 'vendor':
                $orders = Order::partyOrders($user->party->id)
                    ->filteredOrders($role, $status, $search)
                    ->with('chats', 'chats.user', 'orderItems')->orderBy($sortBy, $sortOrder)
                    ->paginate($perPage);
                break;

            case 'factory':
                $orders = Order::partyOrders($user->party->id)
                    ->filteredOrders($role, $status, $search)
                    ->with('chats', 'chats.user', 'orderItems')->orderBy($sortBy, $sortOrder)
                    ->paginate($perPage);
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
        /** @var User $user */
        $user = auth()->user();

        if (!$this->isValidStatusTransition($order->status, $request->status, $user)) {
            return ApiResponse::error('Invalid request', 422);
        }

        try {
            if ($this->isOrderBeingAccepted($order, $request, $user)) {
                if ($user->isVendor()) {
                    $this->handleVendorOrderAcceptance($order, $request);
                } else if ($user->isFactory()) {
                    $this->handleFactoryOrderAcceptance($order, $request);
                } else {
                    return ApiResponse::error('Invalid request', 422);
                }
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

    private function handleVendorOrderAcceptance($order, $request)
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
        $ledger = $order->ledger;
        $ledger->update([
            'last_activity' => Str::afterLast(get_class($order), '\\'),
            'total_order' => $ledger->total_order + $order->quantity,
            'balance_qty' => $ledger->balance_qty + $order->quantity,
            'readyable_qty' => $ledger->readyable_qty + $order->quantity,
            'total_ready' => $ledger->total_ready + $order->quantity,
            'demandable_qty' => $ledger->demandable_qty + $order->quantity,
            'total_demand' => $ledger->total_demand + $order->quantity,
            'dispatchable_qty' => $ledger->dispatchable_qty + $order->quantity,
        ]);
    }

    private function handleFactoryOrderAcceptance($order, $request)
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
        $ledger = $order->ledger;
        $ledger->update([
            'last_activity' => Str::afterLast(get_class($order), '\\'),
            'balance_qty' => $ledger->balance_qty + $order->quantity,
            'readyable_qty' => $ledger->readyable_qty + $order->quantity,
            'total_order' => $ledger->total_order + $order->quantity,
        ]);

        $productMaterials = $order->ledger->product->productMaterials;

        $posCollection = [];
        foreach ($productMaterials as $productMaterial) {

            $material = $productMaterial->material;

            // 1. Check if the material supplier is Monaal Creation
            if ($material->supplier_id != config('monaal.supplier_id')) {
                continue;
            }

            // 2. Create a PO for the material
            $po = Po::create([
                'order_id' => $order->id,
                'material_id' => $material->id,
                'name' => $material->name,
                'product_id' => $order->ledger->product_id,
                'party_id' => $order->ledger->party->id,
                'm_customer_id' => $order->ledger->party->sid,
                'm_customer_name' => $order->ledger->party->business,
                'm_product_id' => Po::removeSupplierPrefix($material->sid),
                'm_order_id' => $order->sid,
                'm_catelog_id' => $order->ledger->product->code,
                'status' => Po::STATUS[0],
                'accepted_at' => Carbon::now(),
                'order_quantity' => $order->quantity,
            ]);     

            $posCollection[$po->order_id . '-' . $po->material_id] = $po;
        }
        
        $poItemsCollection = [];

        foreach ($order->orderItems as $orderItem) {

            // Get the product option for the order item (Black color from t-shirt)
            $orderProductOption = $orderItem->stockItem->productOption;
            $orderProductRange = $orderItem->stockItem->productRange;

            foreach ($orderProductOption->pomos as $pomo) {
                foreach ($orderProductRange->pomrs as $pomr) {
                    
                    // This is the material
                    $optionMaterial = $pomo->materialOption->material;
                    $rangeMaterial = $pomr->productMaterial->material;

                    if ($optionMaterial->id == $rangeMaterial->id) {
                        // Log::info([
                        //     'optionMaterial' => $optionMaterial->name,
                        //     'rangeMaterial' => $rangeMaterial->name,
                        // ]);
                        // $optionMaterial->id . '-' . $rangeMaterial->id
                        $poItemsCollection[] = [
                            'orderitem' => $orderItem->stockItem->name,
                            'material_id' => $optionMaterial->id,
                            'material_name' => $optionMaterial->name,
                            'material_option_id' => $pomo->materialOption->id,
                            'material_option_name' => $pomo->materialOption->name,
                            'material_range_id' => $pomr->material_range_id,
                            'material_range_name' => $pomr->name,
                            'order_quantity' => $orderItem->quantity,
                            'fcpu' => $pomr->quantity,
                            'quantity' => $orderItem->quantity * $pomr->quantity,
                            'rate' => $pomr->cost,
                            'amount' => $orderItem->quantity * $pomr->cost,
                        ];
                    }

                }
            }
            
            // Log::info([
            //     // 'orderProductOption' => $orderProductOption->name,
            //     'color' => $orderProductOption->name,
            //     // 'orderProductRange' => $orderProductRange->name,
            //     'size' => $orderProductRange->name,
            //     'material-colors' => $orderProductOption->pomos->map(function($pomo) {
            //         return $pomo->materialOption->name;
            //     })->toArray(),
            //     'material-ranges' => $orderProductRange->pomrs->map(function($pomr) {
            //         return $pomr->materialRange->width . 'x' . $pomr->materialRange->length . ' @ ' . $pomr->quantity . ' ' . $pomr->unit;
            //     })->toArray(),
            // ]);

            /*
            array (
                'color' => 'BlueRed',
                'size' => 'Free Size',
                'material-colors' => 
                    array (
                        0 => 'FabRed',
                        1 => 'Fab2Blue',
                    ),
                'material-ranges' => 
                    array (
                        0 => '56x160 @ 0.5 Inch',
                        1 => '56x160 @ 2 Inch',
                    ),
            ),  
            array (
                'color' => 'BrownRed',
                'size' => 'Free Size',
                'material-colors' => 
                    array (
                        0 => 'FabRed',
                        1 => 'Fab2Brown',
                    ),
                'material-ranges' => 
                    array (
                        0 => '56x160 @ 0.5 Inch',
                        1 => '56x160 @ 2 Inch',
                    ),
            )  
            */
              
        }

        // Loop thru each order item
        // foreach ($order->orderItems as $orderItem) {

        //     // Get the product option for the order item (Black color from t-shirt)
        //     $orderProductOption = $orderItem->stockItem->productOption;

        //     // Get the material options for the order item (Color of each material required for Black T-shirt)
        //     $materialOptions = $orderProductOption->pomos->pluck('material_option_id');
        //     $orderItemMaterialOptionCollection = [];
        //     /*

        //     Let say we need 2 materials (Cotton, Polyester) for Product1, and
        //     each color will have a correcponsing materials color.
        //     i.e. Color1 of Product1, we need Black1-Cotton, Black2-Polyester

        //     For Black T-short, we need 2 materials (Cotton, Polyester)
        //     array (
        //         1 => 2, i.e. cotton => Black1
        //         2 => 4, i.e. polyester => Black2
        //     ),

        //     For White T-short, we need 2 materials (Cotton, Polyester)
        //     array (
        //         1 => 1, i.e. cotton => White1
        //         2 => 3, i.e. polyester => White2
        //     ),

        //     Log::info([
        //         'orderItemMaterialOptionCollection' => $orderItemMaterialOptionCollection,
        //     ]);

        //     */
        //     foreach ($materialOptions as $materialOptionId) {
        //         $materialOption = MaterialOption::find($materialOptionId);
        //         $orderItemMaterialOptionCollection[$materialOption->material->id] = $materialOption->id;
        //     }
          
        //     // Get the material ranges for the order item (Fcpu of each material required for Black T-shirt)
        //     $groupedPomrs = $orderItem->stockItem->productRange->pomrs->groupBy('product_material_id');
        //     $orderItemMaterialRangeCollection = [];
        //     /*
        //     Let say we need 2 materials (Cotton, Polyester) for Product1, and
        //     each size will have a correcponsing materials range.
        //     i.e. Size1 of Product1, we need x mtr of Cotton, y mtr of Polyester

        //     For Black T-short, we need 2 materials (Cotton, Polyester)
        //     array (
        //         1 => 1, i.e. cotton => x mtr
        //         2 => 3, i.e. polyester => y mtr
        //     ),

        //     For White T-short, we need 2 materials (Cotton, Polyester)
        //     array (
        //         1 => 1, i.e. cotton => x mtr
        //         2 => 3, i.e. polyester => y mtr
        //     ),

        //     Log::info([
        //         'orderItemMaterialRangeCollection' => $orderItemMaterialRangeCollection,
        //     ]);
        //     */
        //     if ($groupedPomrs) {
        //         foreach ($groupedPomrs as $pomrs) {
        //             foreach ($pomrs as $pomr) {
        //                 $orderItemMaterialRangeCollection[$pomr->product_material_id] = $pomr->material_range_id;
        //             }
        //         }
        //     }

        //     foreach ($orderItemMaterialOptionCollection as $materialId => $materialOptionId) {
        //         foreach ($orderItemMaterialRangeCollection as $materialId => $materialRangeId) {

        //             $poItemKey = $order->id . '-' . $materialId . '-' . $materialOptionId . '-' . $materialRangeId;

        //             $material = Material::find($materialId);
        //             $pomo = Pomo::where('product_option_id', $orderProductOption->id)->where('material_option_id', $materialOptionId)->first();
        //             $pomr = Pomr::where('product_range_id', $orderItem->stockItem->productRange->id)
        //                 ->where('material_range_id', $materialRangeId)
        //                 ->where('product_material_id', $materialId)
        //                 ->first();

        //             $poItemsCollection[$poItemKey] = [
        //                 'orderitem' => $orderItem->stockItem->name,
        //                 // 'po_id' => $posCollection[$poItemKey]->id,
        //                 'material_id' => $materialId,
        //                 'material_name' => $material->name,
        //                 'material_option_id' => $materialOptionId,
        //                 'material_option_name' => $pomo->productOption->name,
        //                 'material_range_id' => $materialRangeId,
        //                 'material_range_name' => $pomr->productRange->name,
        //                 'order_quantity' => $orderItem->quantity,
        //                 'fcpu' => $pomr->quantity,
        //                 'quantity' => $orderItem->quantity * $pomr->quantity,
        //                 'rate' => $pomr->cost,
        //                 'amount' => $orderItem->quantity * $pomr->cost,
        //             ];

        //             // Log::info([
        //             //     'poItemKey' => $poItemKey,
        //             //     'pomo' => $pomo,
        //             //     'pomr' => $pomr,
        //             // ]);

        //             // 'poItemKey' => '27-1',
        //             // 'pomo' => array (
        //             //     'id' => 3,
        //             //     'grade' => 0,
        //             //     'product_option_id' => 1,
        //             //     'material_option_id' => 2,
        //             //     'created_at' => '2024-02-03 07:04:35',
        //             //     'updated_at' => '2024-02-03 07:04:35',
        //             //   ),
        //             // )),
        //             // 'pomr' => array (
        //             //     'id' => 1,
        //             //     'name' => 'Sleeve',
        //             //     'cost' => 100.0,
        //             //     'product_range_id' => 1,
        //             //     'material_range_id' => 1,
        //             //     'quantity' => 2.0,
        //             //     'unit' => 'Inch',
        //             //     'grade' => '0',
        //             //     'product_material_id' => 1,
        //             //     'created_at' => '2024-02-02 22:24:15',
        //             //     'updated_at' => '2024-02-03 07:05:28',
        //             //   ),
        //             // )),
        //         }
        //     }
        // }

        Log::info([
            'poItemsCollection' => $poItemsCollection,
        ]);

        // loop thru each poItemCollection and findOrCreate and if found then addup the cuurentloop values to found one fcup, quantity, rate, amount
        // Assuming $poItemsCollection is filled with the items to be processed

        foreach ($posCollection as $key => $po) {
            foreach ($poItemsCollection as $itemData) {
    
                $name = '';
                if ($itemData['material_option_id']) {
                    $materialOption = MaterialOption::find($itemData['material_option_id']);
                    if ($materialOption) {
                        $name .= $materialOption->name;
                    }
                }
    
                if ($itemData['material_range_id']) {
                    $materialRange = MaterialRange::find($itemData['material_range_id']);
                    if ($materialRange) {
                        $name .= ' ' . $materialRange->width . 'x' . $materialRange->length;
                    }
                }

                // $poItemKey = $order->id . '-' . $itemData['material_id'];

                // if($key == $poItemKey) {
                    $existingPoItem = PoItem::updateOrCreate(
                        [
                            'po_id' => $po->id,
                            // 'po_id' => $itemData['po_id'],
                            'material_option_id' => $itemData['material_option_id'],
                            'material_range_id' => $itemData['material_range_id'],
                        ],
                        [
                            'product_option_id' => $itemData['material_option_id'],
                            'product_range_id' => $itemData['material_range_id'],
                            'name' => $name,
                            'order_quantity' => DB::raw("order_quantity + " . $itemData['order_quantity']),
                            // 'fcpu' => DB::raw("fcpu + " . $itemData['fcpu']),
                            'fcpu' => $itemData['fcpu'],
                            'quantity' => DB::raw("quantity + " . $itemData['quantity']),
                            'rate' => $itemData['rate'], // Assuming rate is constant and does not accumulate
                            'amount' => DB::raw("amount + " . $itemData['amount']),
                        ]
                    );
        
                    if (!$existingPoItem->wasRecentlyCreated) {
                        // If the PoItem was found and updated (not newly created), log the update
                        Log::info("Updated PoItem ID {$existingPoItem->id} with cumulative values.");
                    } else {
                        // If the PoItem was newly created, log the creation
                        Log::info("Created new PoItem ID {$existingPoItem->id}.");
                    }
                // }
        
            }
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
