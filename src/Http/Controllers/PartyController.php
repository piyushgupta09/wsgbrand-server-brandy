<?php

namespace Fpaipl\Brandy\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Party;
use Illuminate\Support\Facades\DB;
use Fpaipl\Panel\Http\Controllers\PanelController;
use Fpaipl\Brandy\Datatables\PartyDatatable as Datatable;

class PartyController extends PanelController
{
    public function __construct()
    {
        parent::__construct(
            new Datatable(), 
            'Fpaipl\Brandy\Models\Party', 
            'party', 'parties.index'
        );
    }
   
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'type' => 'required|in:'.Party::PRODUCT_FATORY.','.Party::PRODUCT_VENDOR,
            'active' => 'required|boolean',
            'business' => 'required|string|max:200|unique:parties,business',
            'mobile' => 'required|numeric|digits:10|unique:parties,mobile',
        ]);
        DB::beginTransaction();
        
        $requestUserType = $validated['type'];
        if ($requestUserType == 'product-vendor') {
            $user_type = 'user-vendor';
        } elseif ($requestUserType == 'product-factory') {
            $user_type = 'user-factory';
        }

        $user = User::create([
            'name' => $request->name,
            'type' => $user_type,
            'email' => $request->mobile . '@default.in',
            'password' => bcrypt($request->mobile),
        ]);

        $user->email_verified_at = now();
        $user->mobile = $request->mobile;
        $user->utype = 'mobile';
        $user->save();

        $user->party()->create([
            'user_id' => $user->id,
            'type' => $validated['type'],
            'name' => $request->name,
            'active' => $validated['active'],
            'business' => $validated['business'],
            'mobile' => $validated['mobile'],
        ]);

        $party = $user->party;
        $party->addMediaFromRequest('image')->toMediaCollection($party->getMediaCollectionName());
        $party->tags = implode(',', $party->toArray());
        $party->save();
        
        DB::commit();

        return redirect()->route('parties.index')->with('toast', [
            'class' => 'success',
            'text' => 'Party created successfully.'
        ]);
    }

    public function update(Request $request, Party $party)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required',
                'active' => 'boolean',
            ]);
    
            $party->update($validatedData);
    
            return redirect()->route('parties.show', $party->sid)->with('toast', [
                'class' => 'success',
                'text' => 'Party updated successfully.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // If validation fails, redirect back with the old input
            return redirect()->back()
                             ->withErrors($e->validator)
                             ->withInput();
        }
    }
    
    public function destroy(Request $request, Party $party)
    {
        if ($party->ledger()->exists()) {
            return redirect()->route('parties.index')->with('toast', [
                'class' => 'error',
                'text' => 'Cannot delete party with ledger.'
            ]);
        }

        $party->delete();

        return redirect()->route('parties.index')->with('toast', [
            'class' => 'success',
            'text' => 'Party deleted successfully.'
        ]);
    }
}
