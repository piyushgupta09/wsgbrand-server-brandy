<?php

use Carbon\Carbon;
use Fpaipl\Brandy\Models\Po;
use Fpaipl\Brandy\Models\Order;
use Fpaipl\Brandy\Models\PoItem;
use Illuminate\Support\Facades\Route;
use Fpaipl\Brandy\Http\Controllers\PartyController;
use Fpaipl\Brandy\Http\Controllers\EmployeeController;

Route::middleware(['web','auth'])->group(function () {
    
    Route::resource('parties', PartyController::class);
    Route::resource('employees', EmployeeController::class);

});


