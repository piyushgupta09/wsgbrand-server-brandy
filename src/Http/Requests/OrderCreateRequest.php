<?php

namespace Fpaipl\Brandy\Http\Requests;

use Fpaipl\Brandy\Models\Party;
use Fpaipl\Brandy\Models\Stock;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Cache;
use Fpaipl\Brandy\Http\Fetchers\DsFetcher;
use Fpaipl\Brandy\Rules\FabricatorTypeRule;
use Fpaipl\Brandy\Http\Requests\BaseRequest;

class OrderCreateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'party_sid' => [
                'required', 
                'string', 
                'exists:parties,sid',
                function ($attribute, $value, $fail) {
                    $party = Party::where('sid', $value)->first();
                    if ($party && $party->type !== 'fabricator') {
                        $fail('The :attribute must be of type fabricator.');
                    }
                }
            ],
            'product_sid' => ['required', 'string', 'exists:stocks,product_sid'],
            'quantities' => ['required', 'string'],
            'expected_at' => [
                'sometimes', 
                'date', 
                'after_or_equal:today', 
                'before_or_equal:today + 1 month', 
            ],
        ];
    }

    // after
    // fabricator_sid -> fab app
    // product_sid -> ds app

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                
                if ($this->checkProductWithOptionAndRangeExists()) {
                    $validator->errors()->add(
                        'product_sid',
                        'Invalid product'
                    );
                }
            }
        ];
    }

    private function checkProductWithOptionAndRangeExists()
    {
        $stocks = Stock::where('product_sid', $this->input('product_sid'))->get();
        $quantities = json_decode($this->input('quantities'), true);
        foreach ($quantities as $color_arr) {
            foreach ($color_arr as $color_size_sid => $qty) {
                [$color_sid, $size_sid] = explode('_', $color_size_sid);
                $stock = $stocks->where('product_option_sid', $color_sid)->where('product_range_sid', $size_sid)->first();
                if (!$stock) {
                    return true;
                }
            }
        }
    }
}
