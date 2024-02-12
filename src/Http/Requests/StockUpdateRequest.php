<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Http\Requests\BaseRequest;

class StockUpdateRequest extends BaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'active' => ['required','in:true,false']
        ];
    }
}
