<?php

use Illuminate\Support\Facades\Route;
use Fpaipl\Brandy\Http\Coordinators\RequestCordinator;

Route::middleware(['api', 'auth:sanctum'])->prefix('brand/api')->group(function () {

    Route::resource('requests', RequestCordinator::class);
    
});
