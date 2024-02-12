<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Http\Requests\BaseRequest;

class DispatchCreateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = request()->user();
        return $user->isParty();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ledger_sid' => ['required', 'exists:ledgers,sid'],
            'quantities' => ['required', 'string'],
            'message' => ['nullable', 'string'],
        ];
    }
}
