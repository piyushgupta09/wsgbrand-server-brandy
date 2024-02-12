<?php

namespace Fpaipl\Brandy\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Employee;
use Illuminate\Support\Facades\DB;
use Fpaipl\Panel\Http\Controllers\PanelController;
use Fpaipl\Brandy\Datatables\EmployeeDatatable as Datatable;

class EmployeeController extends PanelController
{
    public function __construct()
    {
        parent::__construct(
            new Datatable(), 
            'Fpaipl\Brandy\Models\Employee', 
            'employee', 'employees.index'
        );
    }
   
    public function store(Request $request)
    {
        $request->validate([
            'info' => 'nullable|string|max:255',
            'name' => 'required|min:3|max:200',
            'active' => 'required|boolean',
            'mobile' => 'required|numeric|digits:10',
        ]);

        DB::beginTransaction();

        $user = User::create([
            'name' => $request->name,
            'type' => 'user-brand',
            'email' => $request->mobile . '@default.in',
            'password' => bcrypt($request->mobile),
        ]);

        $user->email_verified_at = now();
        $user->mobile = $request->mobile;
        $user->utype = 'mobile';
        $user->save();
        
        $employee = Employee::create([
            'user_id' => $user->id,
            'info' => $request->info,
            'name' => $request->name,
            'active' => $request->active,
            'mobile' => $request->mobile,
        ]);

        $employee->addMediaFromRequest('image')->toMediaCollection($employee->getMediaCollectionName());
        $employee->tags = implode(',', $employee->toArray());
        $employee->save();

        DB::commit();

        return redirect()->route('employees.index')->with('toast', [
            'class' => 'success',
            'text' => 'Employee created successfully.'
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'info' => 'nullable',
            'name' => 'required',
            'email' => 'required',
            'mobile' => 'required',
        ]);

        DB::beginTransaction();

        $employee->user->update([
            'name' => $request->name,
        ]);

        $employee->update([
            'info' => $request->info,
        ]);

        return redirect()->route('employees.index')->with('toast', [
            'class' => 'success',
            'text' => 'Employee updated successfully.'
        ]);
    }

    public function destroy(Employee $employee)
    {
        DB::beginTransaction();

        $employee->user->delete();
        $employee->delete();

        DB::commit();

        return redirect()->route('employees.index')->with('toast', [
            'class' => 'success',
            'text' => 'Employee deleted successfully.'
        ]);
    }
}
