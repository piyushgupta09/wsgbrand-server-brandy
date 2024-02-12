<?php

namespace Fpaipl\Brandy\Http\Requests;

use Illuminate\Validation\Validator;
use Fpaipl\Brandy\Http\Fetchers\DsFetcher;
use Fpaipl\Brandy\Http\Requests\BaseRequest;

class StockRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'product_sid' => ['required','string'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->checkProductExistsInDesignStudioApp()) {
                    $validator->errors()->add('product_sid', 'Invalid product');
                }
            }
        ];
    }

    private function checkProductExistsInDesignStudioApp()
    {
        $dsFetcherObj = new DsFetcher();
        $params = $this->input('product_sid').'?'.$dsFetcherObj->api_secret().'&&check=available';
        $response = $dsFetcherObj->makeApiRequest('get', '/api/products/', $params);
        if ($response->statusCode == 200 && $response->status == config('api.ok')) {
            return false;  // product exist
        }
        return true; // product doesn't exists
    }
}
