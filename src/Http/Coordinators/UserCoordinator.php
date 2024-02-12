<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Fpaipl\Panel\Http\Responses\ApiResponse;
use Fpaipl\Brandy\Http\Resources\UserResource;
use Fpaipl\Panel\Http\Coordinators\Coordinator;

class UserCoordinator extends Coordinator
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (env('APP_DEBUG')) {
            Cache::forget('users');
        }

        $users = Cache::remember('users', config('api.cache.duration'), function () use($request) {
            if($request->has('party') && !empty($request->party)){
                if($request->party == 'created'){
                    return User::has('party')->get();
                } else if($request->party == 'not_created'){
                    return User::doesntHave('party')->get();
                }   
            } else {
                return User::get();
            }
        });
        
        return ApiResponse::success(UserResource::collection($users));
    }
    
}