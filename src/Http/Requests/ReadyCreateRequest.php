<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Rules\ValidQuantities;
use Fpaipl\Brandy\Http\Requests\BaseRequest;
use Fpaipl\Brandy\Rules\ValidLedgerFabricator;

class ReadyCreateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        // Only fabricaror can create ready
        return $user->isFactory();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ledger_sid' => ['required', 'exists:ledgers,sid', new ValidLedgerFabricator()],
            'quantities' => ['required', 'string', new ValidQuantities()],
        ];
    }
}
