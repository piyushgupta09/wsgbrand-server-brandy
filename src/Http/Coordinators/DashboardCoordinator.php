<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Fpaipl\Brandy\Models\Po;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Order;
use Illuminate\Support\Facades\DB;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Brandy\Models\Dispatch;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Models\Purchase;
use Fpaipl\Panel\Http\Coordinators\Coordinator;

class DashboardCoordinator extends Coordinator
{
    public function brandDashboard(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('profile');

        if (!$user->isBrand()) {
            return ApiResponse::error('You are not authorized to access this resource.', 403);
        }

        $runningOrders = Ledger::where('balance_qty', '>', 0)->count();

        $totalLedgerbalance = DB::table('ledgers')->sum('balance_qty');

        $activeParties = DB::table('parties')->where('active', 1)->count();

        $stockInHand = DB::table('stocks')->sum('quantity');

        $cardData = [
            [
                'route' => 'BrandProducts',
                'title' => 'Product',
                'icon' => 'bi-stack',
                'border' => '#f45c71',
                'bg' => '#f45c7120',
                'value' => $runningOrders,
            ],
            [
                'route' => 'BrandParties',
                'title' => 'Party',
                'icon' => 'bi-people',
                'border' => '#f45c71',
                'bg' => '#f45c7120',
                'value' => $activeParties,
            ],
            [
                'route' => 'BrandProducts',
                'title' => 'Running',
                'icon' => 'bi-arrow-left-right',
                'border' => '#f45c71',
                'bg' => '#f45c7120',
                'value' => $totalLedgerbalance,
            ],
            [
                'route' => 'BrandProducts',
                'title' => 'Stock',
                'icon' => 'bi-box-seam',
                'border' => '#f45c71',
                'bg' => '#f45c7120',
                'value' => $stockInHand,
            ],

        ];






        // fetch the latest order, demand, ready and dispatch and take last 5
        $orders = DB::table('orders')
            ->join('ledgers', 'orders.ledger_id', '=', 'ledgers.id')
            ->selectRaw("'Order' as class_name, ledgers.sid, orders.quantity, orders.created_at")
            ->orderBy('orders.created_at', 'desc')
            ->take(5)
            ->get();

        $readies = DB::table('readies')
            ->join('ledgers', 'readies.ledger_id', '=', 'ledgers.id')
            ->selectRaw("'Ready' as class_name, ledgers.sid, readies.quantity, readies.created_at")
            ->orderBy('readies.created_at', 'desc')
            ->take(5)
            ->get();

        $demands = DB::table('demands')
            ->join('ledgers', 'demands.ledger_id', '=', 'ledgers.id')
            ->selectRaw("'Demand' as class_name, ledgers.sid, demands.quantity, demands.created_at")
            ->orderBy('demands.created_at', 'desc')
            ->take(5)
            ->get();

        $dispatches = DB::table('dispatches')
            ->join('ledgers', 'dispatches.ledger_id', '=', 'ledgers.id')
            ->selectRaw("'Dispatch' as class_name, ledgers.sid, dispatches.quantity, dispatches.created_at")
            ->orderBy('dispatches.created_at', 'desc')
            ->take(5)
            ->get();

        // Merge, sort, and slice operations remain the same as before
        $ledgers = array_merge($orders->toArray(), $readies->toArray(), $demands->toArray(), $dispatches->toArray());

        usort($ledgers, function ($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });

        $lastActivities = array_slice($ledgers, 0, 5);
        // Assuming the merge, sort, and slice operations have been completed and $lastActivities contains the last 5 activities
        $preparedActivities = array_map(function ($activity) {
            return [
                'event' => $activity->class_name,
                'value' => $activity->quantity,
                'info' => $activity->created_at,
                'action' => 'BrandProductLedger',
                'params' => $activity->sid, // Assuming 'sid' is directly accessible after the join and represents ledger.sid
            ];
        }, $lastActivities);


        $tablesData = [
            [
                [
                    'caption' => 'Daily Activities',
                    'head' => [
                        '#',
                        'Event',
                        'Value',
                        'Action',
                    ],
                    'body' => $preparedActivities,
                ],
            ]
        ];







        // i need total demand that i made in last 6 months, month wise
        $year = 2024;
        $monthlyTotals = DB::table('demands')
            ->join('ledgers', 'demands.ledger_id', '=', 'ledgers.id')
            ->selectRaw('sum(demands.quantity) as total, MONTH(demands.created_at) as month')
            ->where('ledgers.party_id', $user->party_id)
            ->whereYear('demands.created_at', $year)
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();


        // i need total demand that i made in last 6 months, month wise
        $year = 2024;
        $monthlyTotals = DB::table('demands')
            ->join('ledgers', 'demands.ledger_id', '=', 'ledgers.id')
            ->selectRaw('sum(demands.quantity) as total, MONTH(demands.created_at) as month')
            ->whereYear('demands.created_at', $year)
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // also i need total dispatch that i made in last 6 months, month wise
        $monthlyDispatches = DB::table('dispatches')
            ->join('ledgers', 'dispatches.ledger_id', '=', 'ledgers.id')
            ->selectRaw('sum(dispatches.quantity) as total, MONTH(dispatches.created_at) as month')
            ->whereYear('dispatches.created_at', $year)
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();


        // Assuming $user is defined and has a party_id
        $year = 2024; // Example year

        // Prepare the labels for the last 6 months
        // $months = collect(range(6, 1))->map(function($monthOffset) {
        //     return now()->subMonths($monthOffset)->format('M');
        // })->reverse()->toArray();

        $months = collect(range(6, 1))->map(function ($monthOffset) {
            return now()->subMonths($monthOffset)->format('M');
        })->reverse()->values()->toArray();

        // Prepare an array to hold totals for each month for easy access
        $demandsTotals = $monthlyTotals->pluck('total', 'month')->all();
        $dispatchesTotals = $monthlyDispatches->pluck('total', 'month')->all();

        // Convert month numbers to month names and prepare datasets
        $demandsData = [];
        $dispatchesData = [];
        foreach ($months as $month) {
            $monthNum = now()->subMonths(array_search($month, $months))->month;
            $demandsData[] = $demandsTotals[$monthNum] ?? 0; // If no data for the month, default to 0
            $dispatchesData[] = $dispatchesTotals[$monthNum] ?? 0;
        }

        $chartData[] = [
            'titleY' => 'Monthly Activity',
            'data' => [
                'labels' => $months,
                'datasets' => [
                    [
                        'label' => 'Demands',
                        'data' => $demandsData,
                        'borderWidth' => 1,
                        'borderColor' => 'rgba(244, 92, 113, 1)',
                        'backgroundColor' => 'rgba(244, 92, 113, 0.2)',
                    ],
                    [
                        'label' => 'Dispatches',
                        'data' => $dispatchesData,
                        'borderWidth' => 1,
                        'borderColor' => 'rgba(100, 92, 113, 1)',
                        'backgroundColor' => 'rgba(100, 92, 113, 0.2)',
                    ]
                ]
            ],
        ];


        return ApiResponse::success([
            'user' => $user,
            'cards' => $cardData,
            'charts' => $chartData,
            'tables' => $tablesData,
        ]);
    }

    public function factoryDashboard(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('party', 'profile');
        $partyId = $user->party->id;

        if (!$user->isFactory()) {
            return ApiResponse::error('You are not authorized to access this resource.', 403);
        }

        $pendingPos = Po::where('party_id', $partyId)->count();
        $issuedOrders = Order::where('status', 'issued')->where('party_id', $partyId)->count();
        $dispatchOrders = Dispatch::where('party_id', $partyId)->doesntHave('purchases')->count();
        $runningOrders = Ledger::where('balance_qty', '>', 0)->where('party_id', $partyId)->count();

        $cardData = [
            [
                'route' => 'FactoryOrders',
                'title' => 'New Orders',
                'icon' => 'bi-plus-lg',
                'border' => '#054a38',
                'bg' => '#054a3820',
                'value' => $issuedOrders,
            ],
            [
                'route' => 'FactoryPurchaseOrders',
                'title' => 'New Pos',
                'icon' => 'bi-journal-plus',
                'border' => '#054a38',
                'bg' => '#054a3820',
                'value' => $pendingPos,
            ],
            [
                'route' => 'FactoryLedgers',
                'title' => 'Running',
                'icon' => 'bi-arrow-left-right',
                'border' => '#054a38',
                'bg' => '#054a3820',
                'value' => $runningOrders,
            ],
            [
                'route' => 'FactoryDispatches',
                'title' => 'Dispatches',
                'icon' => 'bi-box-arrow-right',
                'border' => '#054a38',
                'bg' => '#054a3820',
                'value' => $dispatchOrders,
            ],

        ];


        $orders = DB::table('orders')
            ->join('ledgers', 'orders.ledger_id', '=', 'ledgers.id')
            ->where('ledgers.party_id', '=', $partyId) // Filter by party ID
            ->selectRaw("'Order' as class_name, ledgers.sid, orders.quantity, orders.created_at")
            ->orderBy('orders.created_at', 'desc')
            ->take(5)
            ->get();
        
        $readies = DB::table('readies')
            ->join('ledgers', 'readies.ledger_id', '=', 'ledgers.id')
            ->where('ledgers.party_id', '=', $partyId) // Filter by party ID
            ->selectRaw("'Ready' as class_name, ledgers.sid, readies.quantity, readies.created_at")
            ->orderBy('readies.created_at', 'desc')
            ->take(5)
            ->get();
        
        $demands = DB::table('demands')
            ->join('ledgers', 'demands.ledger_id', '=', 'ledgers.id')
            ->where('ledgers.party_id', '=', $partyId) // Filter by party ID
            ->selectRaw("'Demand' as class_name, ledgers.sid, demands.quantity, demands.created_at")
            ->orderBy('demands.created_at', 'desc')
            ->take(5)
            ->get();
        
        $dispatches = DB::table('dispatches')
            ->join('ledgers', 'dispatches.ledger_id', '=', 'ledgers.id')
            ->where('ledgers.party_id', '=', $partyId) // Filter by party ID
            ->selectRaw("'Dispatch' as class_name, ledgers.sid, dispatches.quantity, dispatches.created_at")
            ->orderBy('dispatches.created_at', 'desc')
            ->take(5)
            ->get();
        

        // Merge, sort, and slice operations remain the same as before
        $ledgers = array_merge($orders->toArray(), $readies->toArray(), $demands->toArray(), $dispatches->toArray());

        usort($ledgers, function ($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });

        $lastActivities = array_slice($ledgers, 0, 5);
        // Assuming the merge, sort, and slice operations have been completed and $lastActivities contains the last 5 activities
        $preparedActivities = array_map(function ($activity) {
            return [
                'event' => $activity->class_name,
                'value' => $activity->quantity,
                'info' => $activity->created_at,
                'action' => 'FactoryLedgerDetail',
                'params' => $activity->sid, // Assuming 'sid' is directly accessible after the join and represents ledger.sid
            ];
        }, $lastActivities);


        $tablesData = [
            [
                [
                    'caption' => 'Daily Activities',
                    'head' => [
                        '#',
                        'Event',
                        'Value',
                        'Action',
                    ],
                    'body' => $preparedActivities,
                ],
            ]
        ];












        $year = 2024;
        $monthlyTotals = Purchase::select(
            DB::raw('sum(total) as total'),
            DB::raw('MONTH(created_at) as month')
        )
            ->where('party_id', $partyId)
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyData = array_fill(0, 12, 0); // Initialize all months with 0

        foreach ($monthlyTotals as $monthlyTotal) {
            // Assuming month is 1-indexed (1 = January, 2 = February, ...)
            $monthlyData[$monthlyTotal->month - 1] = $monthlyTotal->total;
        }

        $chartData[] = [
            'titleY' => 'Seasons Sales',
            'data' => [
                'labels' => $months,
                'datasets' => [
                    [
                        'label' => '# of Revenue',
                        'data' => $monthlyData,
                        'borderWidth' => 1,
                        'borderColor' => 'rgba(5, 74, 56, 1)',
                        'backgroundColor' => 'rgba(5, 74, 56, 0.75)',
                    ],
                ]
            ],
        ];

        return ApiResponse::success([
            'user' => $user,
            'cards' => $cardData,
            'tables' => $tablesData,
            'charts' => $chartData,
        ]);
    }

    public function vendorDashboard(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('party', 'profile');
        $partyId = $user->party->id;

        if (!$user->isVendor()) {
            return ApiResponse::error('You are not authorized to access this resource.', 403);
        }

        $issuedOrders = Order::where('status', 'issued')->where('party_id', $partyId)->count();
        $dispatchOrders = Dispatch::where('party_id', $partyId)->doesntHave('purchases')->count();
        $runningOrders = Ledger::where('balance_qty', '>', 0)->where('party_id', $partyId)->count();
        $bills = Purchase::where('party_id', $partyId)->count();

        $cardData = [
            [
                'route' => 'FactoryOrders',
                'title' => 'New Orders',
                'icon' => 'bi-plus-lg',
                'border' => '#073b48',
                'bg' => '#073b4820',
                'value' => $issuedOrders,
            ],
            [
                'route' => 'FactoryLedgers',
                'title' => 'Running',
                'icon' => 'bi-arrow-left-right',
                'border' => '#073b48',
                'bg' => '#073b4820',
                'value' => $runningOrders,
            ],
            [
                'route' => 'FactorySales',
                'title' => 'Dispatches',
                'icon' => 'bi-box-arrow-right',
                'border' => '#073b48',
                'bg' => '#073b4820',
                'value' => $dispatchOrders,
            ],
            [
                'route' => 'FactorySales',
                'title' => 'Bills',
                'icon' => 'bi-journal',
                'border' => '#073b48',
                'bg' => '#073b4820',
                'value' => $bills,
            ],
        ];

        // $chartData = [
        //     [
        //         'route' => '/sale-orders',
        //         'title' => 'Sales',
        //         'icon' => 'bi-cart2',
        //         'border' => '#ea0000',
        //         'bg' => '#ea000020',
        //         'value' => $fabPurchases,
        //     ],
        // ];


        $year = 2024;
        $monthlyTotals = Purchase::select(
            DB::raw('sum(total) as total'),
            DB::raw('MONTH(created_at) as month')
        )
            ->where('party_id', $partyId)
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyData = array_fill(0, 12, 0); // Initialize all months with 0

        foreach ($monthlyTotals as $monthlyTotal) {
            // Assuming month is 1-indexed (1 = January, 2 = February, ...)
            $monthlyData[$monthlyTotal->month - 1] = $monthlyTotal->total;
        }

        $chartData[] = [
            'titleY' => 'Seasons Sales',
            'data' => [
                'labels' => $months,
                'datasets' => [
                    [
                        'label' => '# of Revenue',
                        'data' => $monthlyData,
                        'borderWidth' => 1,
                        'borderColor' => 'rgba(7, 59, 72, 1)',
                        'backgroundColor' => 'rgba(7, 59, 72, 0.75)',
                    ],
                ]
            ],
        ];

        // $chartData[] = [
        //     'titleY' => 'Off Seasons Sales',
        //     'data' => [
        //         'labels' => ['Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan'],
        //         'datasets' => [
        //             [
        //                 'label' => '# of Revenue',
        //                 'data' => [3, 5, 2, 3, 10, 9.5],
        //                 'borderWidth' => 1,
        //                 'borderColor' => 'rgba(187, 183, 183, 1)',
        //                 'backgroundColor' => 'rgba(187, 183, 183, 1)',
        //             ],

        //         ]
        //     ],
        // ];

        return ApiResponse::success([
            'user' => $user,
            'cards' => $cardData,
            'charts' => $chartData,
        ]);
    }
}
