<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Http\Requests\BaseRequest;

class PartyCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required','exists:users,id'],
            'sid' => ['required','unique:parties'],
            'type' => ['required', 'in:staff,fabricator,manager'],
            'info' => ['nullable'],
            
        ];
    }
}
