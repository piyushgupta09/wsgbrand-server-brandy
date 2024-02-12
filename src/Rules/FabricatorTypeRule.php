<?php

namespace Fpaipl\Brandy\Rules;

use Closure;
use Fpaipl\Brandy\Models\Party;
use Illuminate\Contracts\Validation\ValidationRule;

class FabricatorTypeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $party = Party::where('sid', $value)->first();
        if ($party && $party->type !== 'fabricator') {
            $fail('The type of :attribute must be fabricator.');
        }
    }
}
