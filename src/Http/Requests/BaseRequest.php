<?php

namespace Fpaipl\Brandy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        // Customize the response format as needed
        $response = new JsonResponse([
            'message' => 'The given data was invalid.',
            'errors' => $errors,
        ], 422);

        throw new HttpResponseException($response);
    }
}
