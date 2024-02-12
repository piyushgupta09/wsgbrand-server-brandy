<?php

namespace Fpaipl\Brandy\Http\Requests;

use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Cache;
use Fpaipl\Brandy\Http\Fetchers\DsFetcher;
use Fpaipl\Brandy\Rules\FabricatorTypeRule;
use Illuminate\Foundation\Http\FormRequest;

class LedgerCreateRequest extends FormRequest
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
            'party_sid' => ['required', 'string', 'exists:parties,sid', new FabricatorTypeRule],
            'product_sid' => ['required', 'string', 'exists:stocks,product_sid'],
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
                    $validator->errors()->add(
                        'product_sid',
                        'Invalid product'
                    );
                }
            }
        ];
    }

    private function checkProductExistsInDesignStudioApp()
    {
        $dsFetcherObj = new DsFetcher();
        $params = $this->input('product_sid').'?'.$dsFetcherObj->api_secret();
        $response = $dsFetcherObj->makeApiRequest('get', '/api/products/', $params);
        if ($response->statusCode == 200 && $response->status == config('api.ok')) {
            // redis store $response->product key product_slug + today('ddmmyy')
            Cache::put($this->input('product_sid').date('dmy'), $response->data);
            return false;  // product exist
        }
        return true; // product doesn't exists
    }
}
