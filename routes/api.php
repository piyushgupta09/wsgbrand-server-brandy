<?php

use Illuminate\Support\Facades\Route;
use Fpaipl\Brandy\Http\Coordinators\SyncCoordinator;
use Fpaipl\Brandy\Http\Coordinators\{
    MonaalCoordinator,
    ChatCoordinator,
    UserCoordinator,
    OrderCoordinator,
    PartyCoordinator,
    ReadyCoordinator,
    StockCoordinator,
    DemandCoordinator,
    LedgerCoordinator,
    DispatchCoordinator,
    PurchaseCoordinator,
    DashboardCoordinator,
    AdjustmentCoordinator
};

$assignableRoles = config('panel.assignable-roles');
$brandAccessRoles = array_column($assignableRoles['user-brand'], 'id');
$vendorAccessRoles = array_column($assignableRoles['user-vendor'], 'id');
$factoryAccessRoles = array_column($assignableRoles['user-factory'], 'id');
$partyAccessRoles = array_unique(array_merge($factoryAccessRoles, $vendorAccessRoles));
$commonAccessRoles = array_unique(array_merge($brandAccessRoles, $vendorAccessRoles, $factoryAccessRoles));

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->group(
    function () use ($brandAccessRoles, $vendorAccessRoles, $factoryAccessRoles, $partyAccessRoles, $commonAccessRoles) {
    
    Route::middleware('role:' . implode('|', $commonAccessRoles))->group(function () {
        Route::controller(ChatCoordinator::class)->group(function () {
            // For: Single chat item
            Route::post('chats', 'store');
            Route::put('chats/{chat}', 'update');
            // For: All chat of a ledger
            Route::get('chats/{ledger}', 'show');
            Route::delete('chats/{ledger}', 'destroy');
        });
        Route::controller(LedgerCoordinator::class)->group(function () {
            Route::get('ledgers/{ledger}', 'show');
        });
        Route::controller(OrderCoordinator::class)->group(function () {
            Route::get('orders', 'index');
            Route::get('orders/{order}', 'show');
            Route::put('orders/{order}', 'update');
        });
        Route::controller(DispatchCoordinator::class)->group(function () {
            Route::get('dispatches', 'index');
            Route::put('dispatches/{dispatch}', 'update');
            Route::delete('dispatches/{dispatch}', 'destroy');
        });
        Route::controller(AdjustmentCoordinator::class)->group(function () {
            Route::post('adjustments', 'store');
        });
        Route::controller(PurchaseCoordinator::class)->group(function () {
            Route::get('purchases', 'index');
        });
    });

    // BRAND
    Route::middleware('role:' . implode('|', $brandAccessRoles))->prefix('brand')->group(function () {
        Route::get('dashboard', [DashboardCoordinator::class, 'brandDashboard']);
        // name('app.') is addes to avoid conflit with web resource routes
        Route::name('app.')->group(function () {
            Route::apiResource('users', UserCoordinator::class);
            Route::apiResource('parties', PartyCoordinator::class);
        });
        Route::controller(OrderCoordinator::class)->group(function () {
            Route::post('orders', 'store');
            Route::delete('orders/{order}', 'destroy');
        });
        Route::controller(DemandCoordinator::class)->group(function () {
            Route::post('demands', 'store');
            Route::put('demands/{demand}', 'update');
            Route::delete('demands/{demand}', 'destroy');
        });
        Route::controller(PurchaseCoordinator::class)->group(function () {
            Route::post('purchases', 'store');
        });
        
        // Route::controller(StockCoordinator::class)->group(function () {
        //     Route::get('stocks', 'brandIndex');
        //     Route::get('stocks/{stock}', 'show');
        //     Route::post('stocks/query', 'query');
        //     Route::post('stocks', 'store');
        //     Route::put('stocks/{stock}', 'update');
        // });
    });

    // VENDOR & FACTORY

    Route::middleware('role:' . implode('|', $partyAccessRoles))->prefix('party')->group(function () {
        Route::controller(LedgerCoordinator::class)->group(function () {
            Route::get('ledgers', 'partyIndex');
        });
        Route::controller(DispatchCoordinator::class)->group(function () {
            Route::post('dispatches', 'store');
            Route::put('dispatches/{dispatch}', 'update');
            Route::delete('dispatches/{dispatch}', 'destroy');
        });
    });

    Route::middleware('role:' . implode('|', $factoryAccessRoles))->prefix('factory')->group(function () {
        Route::get('dashboard', [DashboardCoordinator::class, 'factoryDashboard']);
        Route::controller(ReadyCoordinator::class)->group(function () {
            Route::post('readies', 'store');
            Route::put('readies/{ready}', 'update');
            Route::delete('readies/{ready}', 'destroy');
        });
        Route::controller(MonaalCoordinator::class)->group(function () {
            Route::get('pos', 'poindex');
            Route::get('bills', 'billIndex');
        });
    });

    Route::middleware('role:' . implode('|', $vendorAccessRoles))->prefix('vendor')->group(function () {
        Route::get('dashboard', [DashboardCoordinator::class, 'vendorDashboard']);
        Route::controller(LedgerCoordinator::class)->group(function () {
            Route::get('ledgers/{ledger}', 'showWithDispatches');
        });
    });

});

Route::middleware(['api'])->prefix('api')->group(function () {
    
    // Monaal will access this route to sync data
    Route::prefix('sync')->group(function () {
        Route::post('sos', [SyncCoordinator::class, 'sendSos']);
        Route::post('parties', [SyncCoordinator::class, 'sendParties']);
    });

});


// Route::middleware(['api', 'auth:sanctum'])->prefix('api')->group(function () {

//     Route::middleware('role:manager|staff|fabricator')->group(function () {
//         Route::get('dashboard', [DashboardCoordinator::class, 'index']);

//         Route::controller(DispatchCoordinator::class)->group(function () {
//             Route::get('dispatches', 'index');
//             Route::put('dispatches/{dispatch}', 'update');
//             Route::delete('dispatches/{dispatch}', 'destroy');
//         });
//         Route::controller(AdjustmentCoordinator::class)->group(function () {
//             Route::post('adjustments', 'store');
//         });
//         Route::controller(ChatCoordinator::class)->group(function () {
//             Route::get('chats/{ledger_sid}', 'show');
//             Route::post('chats', 'store');
//             Route::put('chats/{chat}', 'update');
//             Route::delete('chats/{chat}', 'destroy');
//         });

//     });



//     Route::middleware('role:manager|staff')->group(function () {
//         Route::name('app.')->group(function () {
//             Route::apiResource('users', UserCoordinator::class);
//             Route::apiResource('parties', PartyCoordinator::class);
//         });
//         Route::controller(StockCoordinator::class)->group(function () {
//             Route::get('stocks', 'brandIndex');
//             Route::get('stocks/{stock}', 'show');
//             Route::post('stocks/query', 'query');
//             Route::post('stocks', 'store');
//             Route::put('stocks/{stock}', 'update');
//         });
//         Route::controller(LedgerCoordinator::class)->group(function () {
//             Route::get('ledgers', 'index');
//             Route::post('ledgers', 'store');
//             Route::put('ledgers/{ledger}', 'update');
//             Route::delete('ledgers/{ledger}', 'destroy');
//         });


//         Route::controller(AdjustmentCoordinator::class)->group(function () {
//             Route::put('adjustments/{adjustment}', 'update');
//             Route::delete('adjustments/{adjustment}', 'destroy');
//         });

//         Route::controller(DsFetcher::class)->prefix('ds')->group(function () {
//             Route::get('products', 'allProducts');
//             Route::get('products/{sid}', 'showProduct');
//             Route::get('product_skus', 'allProductSkus');
//             Route::get('product_skus/{sku}', 'showProductSku');
//         });
//     });
// });


/**
 *  1. LedgerCoordinator
 *     - show (GET /ledgers/{ledger})
 *     - index (GET /ledgers)
 *     - store (POST /ledgers)
 *     - update (PUT /ledgers/{ledger})
 *     - destroy (DELETE /ledgers/{ledger})
 *  
 *  2. OrderCoordinator
 *     - index (GET /orders)
 *     - show (GET /orders/{order})
 *     - store (POST /orders)
 *     - update (PUT /orders/{order})
 *     - destroy (DELETE /orders/{order})
 *  
 *  3. DispatchCoordinator
 *     - index (GET /dispatches)
 *     - store (POST /dispatches)
 *     - update (PUT /dispatches/{dispatch})
 *     - destroy (DELETE /dispatches/{dispatch})
 *  
 *  4. ChatCoordinator
 *     - store (POST /chats)
 *     - update (PUT /chats/{chat})
 *     - destroy (DELETE /chats/{chat})
 *  
 *  5. StockCoordinator
 *     - fabIndex (GET /mystocks)
 *     - brandIndex (GET /stocks)
 *     - show (GET /stocks/{stock})
 *     - query (POST /stocks/query)
 *     - store (POST /stocks)
 *     - update (PUT /stocks/{stock})
 *  
 *  6. ReadyCoordinator
 *     - store (POST /readies)
 *     - update (PUT /readies/{ready})
 *     - destroy (DELETE /readies/{ready})
 *  
 *  7. PoCoordinator
 *     - store (POST /pos)
 *     - update (PUT /pos/{po})
 *     - destroy (DELETE /pos/{po})
 *     - checkProcurement (POST /procurement/check)
 *  
 *  8. UserCoordinator (handled by Route::apiResource)
 *     - index (GET /users)
 *     - store (POST /users)
 *     - show (GET /users/{user})
 *     - update (PUT /users/{user})
 *     - destroy (DELETE /users/{user})
 *  
 *  9. PartyCoordinator (handled by Route::apiResource)
 *     - index (GET /parties)
 *     - store (POST /parties)
 *     - show (GET /parties/{party})
 *     - update (PUT /parties/{party})
 *     - destroy (DELETE /parties/{party})
 *  
 *  10. DemandCoordinator
 *      - store (POST /demands)
 *      - update (PUT /demands/{demand})
 *      - destroy (DELETE /demands/{demand})
 *  
 *  11. AdjustmentCoordinator
 *      - store (POST /adjustments)
 *      - update (PUT /adjustments/{adjustment})
 *      - destroy (DELETE /adjustments/{adjustment})
 *  
 *  12. PurchaseCoordinator
 *      - store (POST /purchases)
 *      - index (GET /purchases)
 *  
 *  13. DsFetcher
 *      - allProducts (GET /ds/products)
 *      - showProduct (GET /ds/products/{sid})
 *      - allProductSkus (GET /ds/product_skus)
 *      - showProductSku (GET /ds/product_skus/{sku})
 */
