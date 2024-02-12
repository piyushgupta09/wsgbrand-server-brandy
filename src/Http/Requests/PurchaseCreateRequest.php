<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Http\Requests\BaseRequest;

class PurchaseCreateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'nullable|string|max:255',
            'dispatch_sid' => ['required', 'exists:dispatches,sid'],
            'quantities' => 'required|json',
            'doc_date' => ['required', 'date', 'after_or_equal:today'],
            'doc_id' => 'required',
        ];
    }
}
