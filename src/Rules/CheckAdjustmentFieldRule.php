<?php

namespace Fpaipl\Brandy\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckAdjustmentFieldRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
    }
}
