
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

        // Loop thru each order item
        foreach ($order->orderItems as $orderItem) {

            // Get the product option for the order item (Black color from t-shirt)
            $orderProductOption = $orderItem->stockItem->productOption;

            // Get the material options for the order item (Color of each material required for Black T-shirt)
            // ex:- for Black T-shirt, we need 2 materials (Cotton, Polyester)
            // so we need Black1-Cotton, Black2-Polyester
            $materialOptions = $orderProductOption->pomos->pluck('material_option_id');
        
            $orderItemMaterialOptionCollection = [];

            foreach ($materialOptions as $materialOptionId) {

                $materialOption = MaterialOption::find($materialOptionId);
                // Log::info(['materialOption' => $materialOption->toArray()]);

                $orderItemMaterialOptionCollection[$materialOption->material->id] = $materialOption->id;
                    // 'po_id' => $po->id,
                    // 'material_id' => $materialOption->material->id,
                    // 'material_option_id' => $materialOption->id,
                    // 'material_range_id' => $pomr->material_range_id,
                    // 'product_option_id' => $productOptionId,
                    // 'product_range_id' => $pomr->product_range_id,
                    // 'order_quantity' => $orderItem->quantity,
                    // 'fcpu' => $pomr->quantity,
                    // 'quantity' => $orderItem->quantity * $pomr->quantity,
                    // 'rate' => $pomr->cost,
                    // 'amount' => $orderItem->quantity * $pomr->quantity * $pomr->cost,
                // ];

            }

            Log::info([
                'orderItemMaterialOptionCollection' => $orderItemMaterialOptionCollection,
            ]);

            /*
            Let say we need 2 materials (Cotton, Polyester) for Product1, and
            each color will have a correcponsing materials color.
            i.e. Color1 of Product1, we need Black1-Cotton, Black2-Polyester

            For Black T-short, we need 2 materials (Cotton, Polyester)
            array (
                1 => 2, i.e. cotton => Black1
                2 => 4, i.e. polyester => Black2
            ),

            For White T-short, we need 2 materials (Cotton, Polyester)
            array (
                1 => 1, i.e. cotton => White1
                2 => 3, i.e. polyester => White2
            ),

            */

          
            // First, group pomrs by product_material_id
            $groupedPomrs = $orderItem->stockItem->productRange->pomrs->groupBy('product_material_id');

            // Filter to only include the group where product_material_id matches material->id
            // $pomrs = $groupedPomrs->get($materialId);

            // Log::info([
                // 'orderProductOption' => $orderProductOption->toArray(),
                // 'materialOptions' => $materialOptions->toArray(),
                // 'groupedPomrs' => $groupedPomrs->toArray(),
                // 'pomrs' => $pomrs->toArray(),
            // ]);

            $orderItemMaterialRangeCollection = [];
            // If the relevant group exists, take the first item and select the desired fields
            if ($groupedPomrs) {
                foreach ($groupedPomrs as $pomrs) {
                    foreach ($pomrs as $pomr) {
                        $orderItemMaterialRangeCollection[$pomr->product_material_id] = $pomr->material_range_id;
                    }
                    // // Takes first available fcpu and quantity
                    // $pomr = $pomrs->first();
                }
            }

            /*
            Let say we need 2 materials (Cotton, Polyester) for Product1, and
            each size will have a correcponsing materials range.
            i.e. Size1 of Product1, we need x mtr of Cotton, y mtr of Polyester

            For Black T-short, we need 2 materials (Cotton, Polyester)
            array (
                1 => 1, i.e. cotton => x mtr
                2 => 3, i.e. polyester => y mtr
            ),

            For White T-short, we need 2 materials (Cotton, Polyester)
            array (
                1 => 1, i.e. cotton => x mtr
                2 => 3, i.e. polyester => y mtr
            ),

            */

            Log::info([
                'orderItemMaterialRangeCollection' => $orderItemMaterialRangeCollection,
            ]);
        }

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

                $poItemKey = $order->id . '-' . $itemData['material_id'];

                if($key == $poItemKey) {
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
                }
        
            }
        }

        
    }
