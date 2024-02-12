<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Rules\ValidQuantities;
use Fpaipl\Brandy\Rules\ValidLedgerManager;
use Fpaipl\Brandy\Http\Requests\BaseRequest;

class DemandCreateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        // Only manager can create demand
        return $user->isManagerBrand() || $user->isOrderManagerBrand();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ledger_sid' => ['required', 'exists:ledgers,sid', new ValidLedgerManager()],
            // 'quantities' => ['required', 'string', new ValidQuantities()],
            'expected_at' => 'required|after_or_equal:today|date_format:Y-m-d',
            // 'tolerance' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
