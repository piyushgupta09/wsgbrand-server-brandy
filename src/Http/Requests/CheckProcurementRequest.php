<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Http\Requests\BaseRequest;

class CheckProcurementRequest extends BaseRequest
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
            'order_sid' => ['required', 'exists:orders,sid'],
        ];
    }
}
