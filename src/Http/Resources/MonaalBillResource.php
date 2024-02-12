<?php

namespace Fpaipl\Brandy\Http\Resources;

use Fpaipl\Brandy\Models\Po;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonaalBillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Update the status of the POs
        if (!empty($this->pos) && is_string($this->pos) && json_decode($this->pos)) {
            $posSids = json_decode($this->pos);

            foreach ($posSids as $posSid) {
                $po = Po::where('sid', $posSid)->first();
                if ($po) {
                    $po->status = 'completed';
                    $po->save();
                }
            }
        }

        return [
            'uuid' => $this->uuid,
            'doc_no' => $this->doc_no,
            'doc_date' => $this->doc_date,
            'customer_sid' => $this->customer_sid,
            'status' => $this->status,
            'note' => $this->note,
            'amount' => $this->amount,
            'payable' => $this->payable,
            'balance' => $this->balance,
            'completed_by' => $this->completed_by,
            'completed_at' => $this->completed_at,
            'paid_at' => $this->paid_at,
        ];
    }
}