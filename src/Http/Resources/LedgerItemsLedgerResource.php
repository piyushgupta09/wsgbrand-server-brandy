<?php

namespace Fpaipl\Brandy\Http\Resources;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Resources\UserResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Fpaipl\Brandy\Http\Resources\LedgerItemsResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LedgerItemsLedgerResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        // return parent::toArray($request);
        $flattenedItems = $this->collection->collapse()->map(function ($item) use($request) {
            
            // Convert each item into a LedgerItemsResource and then group
            $ledgerItemsResources = LedgerItemsResource::collection($item->items())->toArray($request);

            $groupedItems = collect($ledgerItemsResources)->groupBy('groupId')->map(function ($group) {
                $firstItem = $group->first();
                $totalQuantity = $group->sum('quantity');
                return [
                    'quantity' => $totalQuantity,
                    'option' => $firstItem['option'],
                    'image' => $firstItem['image'],
                    'preview' => $firstItem['preview'],
                    'ranges' => $group->map(function ($item) {
                        return [
                            'range' => $item['range'],
                            'quantity' => $item['quantity'],
                            'mrp' => $item['mrp'],
                            'rate' => $item['rate'],
                        ];
                    })->values(),
                ];
            });

            return [
                'user' => new UserResource($item->user),
                'model' => Str::slug(Str::afterLast(get_class($item), '\\')),
                'model_sid' => $item->sid,
                'quantity' => $item->quantity,
                'type' => $item->type ?? null, // Only for adjustments
                'expected_at' => $item->expected_at ?? null, // Some items might not have expected_at
                'log_status_time' => json_decode($item->log_status_time ?? null), // Only for orders
                'status' => $item->status ?? null, // Some items might not have status
                // Adjustment Only
                'chat' => [
                    'type' => $item->chats ? $item->chats->first()?->type : 'text',
                    'content' => $item->chats ? $item->chats->first()?->content : null,
                ],
                'created_on' => Carbon::parse($item->created_at)->format('Y-m-d'),
                'created_at' => $item->created_at,
                'items' => $groupedItems->values(), // Ensure the groups are represented as arrays
            ];
        });

        // Sort the collection by 'created_at'
        $flattenedItems = $flattenedItems->sort(function ($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        // Implementing custom pagination
        return $this->paginateCollection($flattenedItems, 5)->toArray();
    }


    /**
     * Paginate a collection.
     *
     * @param \Illuminate\Support\Collection $collection
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginateCollection($collection, $perPage)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedItems = new LengthAwarePaginator($currentItems, $collection->count(), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        return $paginatedItems;
    }
}
