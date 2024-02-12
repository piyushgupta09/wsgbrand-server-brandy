<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Http\Requests\BaseRequest;

class OrderUpdateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required','in:issued,accepted,cancelled,rejected,deleted'],
            'expected_at' => ['sometimes', 'date', 'after_or_equal:today', 'before_or_equal:today + 1 year'], // 'date_format:Y-m-d'
            'content' => ['nullable', 'string', 'max:255'],
        ];
    }
}
