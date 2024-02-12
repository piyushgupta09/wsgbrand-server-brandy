<?php

namespace Fpaipl\Brandy\Actions;

use Fpaipl\Brandy\Models\MonaalBill;
use Fpaipl\Panel\Services\Syncme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LoadPos
{
    public static function execute($customerSid)
    {
        try {
            DB::beginTransaction();

            $response = Syncme::get('sales/' . $customerSid);
            Log::info('SyncSos response: ' . json_encode($response));

            if (!empty($response['status']) && $response['status'] === 'success') {
                foreach ($response['data'] as $data) {
                    // Validate each field
                    $validator = Validator::make($data, [
                        'monaal_id' => 'required|integer',
                        'doc_no' => 'required|string|max:255',
                        'doc_date' => 'required|date',
                        'customer_sid' => 'required|string|max:255',
                        'status' => 'required|in:draft,issued,partial,completed,cancelled',
                        'amount' => 'required|numeric',
                        'payable' => 'required|numeric',
                        'balance' => 'required|numeric',
                    ]);

                    if ($validator->fails()) {
                        Log::error('Validation failed for Monaal Bill: ' . json_encode($validator->errors()));
                        continue;
                    }

                    // Use updateOrCreate to either update an existing record or create a new one
                    MonaalBill::updateOrCreate(
                        [
                            'monaal_id' => $data['monaal_id'],
                            'customer_sid' => $data['customer_sid'],
                        ],
                        [
                            'doc_no' => $data['doc_no'],
                            'doc_date' => $data['doc_date'],
                            'status' => $data['status'],
                            'amount' => $data['amount'],
                            'payable' => $data['payable'],
                            'balance' => $data['balance'],
                            'pos' => json_encode($data['sos']),
                            'details' => json_encode($data['details']),
                        ]
                    );

                    Log::info('Successfully synchronized Monaal Bill: ' . $data['doc_no']);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SyncMonaalBills error: ' . $e->getMessage());
        }
    }
}
