<?php

namespace Fpaipl\Brandy\Rules;

use Closure;
use Fpaipl\Brandy\Models\StockItem;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidQuantities implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $quantities = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $fail('The :attribute must be valid json.');
            return;
        }

        foreach ($quantities as $quantityObj) {
            foreach ($quantityObj as $key => $val) {
                [$productOption, $productRange] = explode('_', $key);

                if (!StockItem::where('product_option_sid', $productOption)->exists()) {
                    $fail("Invalid product option '{$productOption}' in :attribute.");
                    return;
                }

                if (!StockItem::where('product_range_sid', $productRange)->exists()) {
                    $fail("Invalid product range '{$productRange}' in :attribute.");
                    return;
                }

                if (!is_numeric($val) || $val < 1) {
                    $fail("The quantity for '{$key}' in :attribute must be a valid integer and at least 1.");
                    return;
                }
            }
        }
    }
}
