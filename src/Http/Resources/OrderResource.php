<?php

namespace Fpaipl\Brandy\Http\Resources;

use Carbon\Carbon;
use Fpaipl\Brandy\Models\Po;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Panel\Services\Syncme;
use Illuminate\Support\Facades\Log;
use Fpaipl\Prody\Models\ProductOption;
use Fpaipl\Brandy\Http\Resources\PoResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Fpaipl\Brandy\Http\Resources\LedgerItemsResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // // first fetch the party from order
        // $party = $this->party;
        // $partyPos = $party->pos;

        // // loop through the pos and make a collection of pos which are not completed
        // $pendingPos = collect();
        // foreach ($partyPos as $po) {
        //     // Skip if po is completed
        //     if ($po->status != Po::STATUS[3]) {
        //         $pendingPos->push($po);
        //     }
        // }

        // // check if list not empty
        // if ($pendingPos->isNotEmpty()) {
        //     $customerSid = $this->party->sid;
        //     $response = Syncme::get('sos/' . $customerSid);
        //     Log::info('Syncme API called to fetch sos data for customer: ' . $customerSid . ' with response: ' . json_encode($response));

        //     // Check if the response has status 'success'
        //     if ($response && $response['status'] === 'success' && !empty($response['data'])) {
        //         foreach ($response['data'] as $soData) {
        //             // Find the corresponding PO in your pending POs
        //             $poToUpdate = Po::find($soData['wsg_po_id']);

        //             // If a matching PO is found, update it
        //             if ($poToUpdate) {
        //                 $poToUpdate->sid = $soData['sid'] ?? null;
        //                 $poToUpdate->m_customer_id = $soData['customer_id'] ?? null;
        //                 $poToUpdate->m_product_id = $soData['product_id'] ?? null;
        //                 $poToUpdate->m_order_id = $soData['order_id'] ?? null;
        //                 $poToUpdate->m_catelog_id = $soData['catelog_id'] ?? null;
        //                 $poToUpdate->status = $soData['status'];
        //                 // $poToUpdate->pre_order = $soData['pre_order'] ?? null;
        //                 $poToUpdate->rate = $soData['rate'];
        //                 $poToUpdate->quantity = $soData['quantity'] ?? null;
        //                 $poToUpdate->amount = $soData['amount'];
        //                 $poToUpdate->issued_by = $soData['issued_by'] ?? null;
        //                 $poToUpdate->issued_at = $soData['issued_at'] ?? null;
        //                 $poToUpdate->completed_by = $soData['completed_by'] ?? null;
        //                 $poToUpdate->completed_at = $soData['completed_at'] ?? null;
        //                 $poToUpdate->uuid = $soData['uuid'] ?? null;
        //                 $poToUpdate->update();
        //             }
        //         }
        //     } else {
        //         Log::info('Syncme API failed to fetch sos data for customer: ' . $customerSid . ' with response: ' . json_encode($response));
        //     }
        // }

        // if expected date is passed, then show the date otherwise show diffForHumans
        $carbonDate = Carbon::parse($this->expected_at);
        $expectedDate = $carbonDate->isPast() ? 'Was Due On ' . $carbonDate->format('d-m-Y') : 'Due in ' . $carbonDate->diffForHumans();

        $itemsResources = LedgerItemsResource::collection($this->orderItems)->toArray($request);
        $groupedItems = collect($itemsResources)->groupBy('groupId')->map(function ($group) {
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
            'sid' => $this->sid,
            'image' => $this->ledger->getImage(ProductOption::MEDIA_CONVERSION_THUMB),
            'preview' => $this->ledger->getImage(ProductOption::MEDIA_CONVERSION_PREVIEW),
            'product_code' => $this->ledger->product->code,
            'product_name' => $this->ledger->product->name,
            'fab_rate' => $this->ledger->fab_rate ?? 100,
            'quantity' => $this->quantity,
            'expected_at' => $this->expected_at,
            'created_at' => Carbon::parse($this->created_at)->format('d-m-Y'),
            'due_date' => $expectedDate,
            'status' => $this->status,
            'orderfrom' => $this->user->name ?? null,
            'content' => $this->chats->first()->content ?? null,
            'items' => $groupedItems,
            'tags' => $this->tags,
            'party' => [
                'sid' => $this->party->sid,
                'name' => $this->party->business,
                'mobile' => $this->party->mobile,
                'type' => $this->party->type,
                'image' => $this->party->getImage(Party::MEDIA_CONVERSION_THUMB),
            ],
            'pos' => PoResource::collection($this->whenLoaded('pos')),
        ];
    }
}