<?php

namespace Fpaipl\Brandy\Rules;

use Closure;
use Fpaipl\Brandy\Models\Ledger;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidLedgerFabricator implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {    
        /** @var User $user */
        $user = auth()->user();

        $ledger = Ledger::where('party_id', $user->party->id)->where('sid', $value)->exists();

        if (!$ledger) {
            $fail('Invalid party');
        }

    }
}
