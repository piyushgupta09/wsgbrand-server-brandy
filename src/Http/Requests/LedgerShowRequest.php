<?php

namespace Fpaipl\Brandy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LedgerShowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        $ledger = $this->route('ledger'); // Retrieve the Ledger instance from the route

        // Allow managers and staff to access any ledger
        if ($user->isManager() || $user->isStaff()) {
            return true;
        }

        // Allow fabricators to access only their own ledgers
        if ($user->isFabricator()) {
            return $ledger->party_id === $user->party->id;
        }

        // Deny access by default
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
