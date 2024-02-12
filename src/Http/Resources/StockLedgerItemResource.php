<?php

namespace Fpaipl\Brandy\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Resources\UserResource;
use Fpaipl\Brandy\Http\Resources\LedgerItemsResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StockLedgerItemResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        $flattenedItems = $this->collection->collapse()->map(function ($item) {
            return [
                'user' => new UserResource($item->user),
                'model' => Str::slug(Str::afterLast(get_class($item), '\\')),
                'model_sid' => $item->sid,
                'quantity' => $item->quantity,

                'expected_at' => $item->expected_at ?? null, // Some items might not have expected_at
                'log_status_time' => json_decode($item->log_status_time ?? null), // Only for orders
                'status' => $item->status ?? null, // Some items might not have status
                // Adjustment Only
                'note' => $item->note ?? null,
                'type' => $item->type ?? null,
               
                'created_at' => $item->created_at,
                'items' => LedgerItemsResource::collection($item->items()),
            ];
        })->toArray();

        // Sort the array by 'created_at'
        usort($flattenedItems, function ($a, $b) {
            return strtotime($a['created_at']) <=> strtotime($b['created_at']);
        });

        return $flattenedItems;
    }
}
