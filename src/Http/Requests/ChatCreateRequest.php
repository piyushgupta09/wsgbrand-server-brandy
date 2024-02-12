<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Models\Chat;
use Illuminate\Foundation\Http\FormRequest;

class ChatCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // get the auth user if it isBrand, then allowed, 
        // else if it isParty then find the ledger by sid and 
        // check that the ledger->party->user->id must match the 
        // auth user only then he is allowed, also not allowed in any other case
        return $this->user()->isBrand() || $this->user()->isParty() && $this->user()->party->ledgers()->where('sid', $this->ledger_sid)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'ledger_sid' => 'required|string|exists:ledgers,sid',
            'type' => ['required','string', 'in:'.implode(',', array_keys(Chat::TYPES))],
            'content' => 'required_if:type,text|string|nullable',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'ledger_sid.required' => 'The ledger ID is required.',
            'ledger_sid.exists' => 'The specified ledger does not exist.',
            'type.required' => 'The message type is required.',
            'type.in' => 'The message type is invalid.',
            'content.required_if' => 'The message content is required.',
        ];
    }
}
