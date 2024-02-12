<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Rules\ValidQuantities;
use Fpaipl\Brandy\Rules\ValidLedgerManager;
use Fpaipl\Brandy\Http\Requests\BaseRequest;

class AdjustmentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        // /** @var User $user */
        // $user = auth()->user();

        // // Only manager can create demand
        // return $user->isManager() || $user->isStaff();
        return true;
    }

    public function rules(): array
    {
        return [
            'ledger_sid' => ['required', 'exists:ledgers,sid', new ValidLedgerManager()],
            'quantities' => ['required', 'string', new ValidQuantities()],
            'content' => 'nullable|string|min:1',
            'type' => 'required|in:order,ready,demand',
        ];
    }
}
