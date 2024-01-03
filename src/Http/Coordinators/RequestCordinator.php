<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Illuminate\Http\Request;

class RequestCordinator
{
    public function index(Request $request)
    {
        return response()->json([
            'message' => 'Hello World'
        ]);
    }
}