<?php

namespace Fpaipl\Brandy\Http\Coordinators;

use Fpaipl\Brandy\Models\Po;
use Illuminate\Http\Request;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Panel\Http\Coordinators\Coordinator;
use Fpaipl\Brandy\Http\Resources\MonaalSoResource;
use Fpaipl\Brandy\Http\Resources\MonaalCustomerResource;

/**
 * Handles synchronization operations for sales orders (SOs) and parties.
 * This class provides endpoints for external systems (monaal.in) 
 * to sync sales orders and party information with the application.
 * It ensures data integrity and security by validating request tokens.
 * 
 * @package Fpaipl\Brandy\Http\Coordinators
 */
class SyncCoordinator extends Coordinator
{
    /**
     * Synchronizes sales orders (SOs) from an external system into the application.
     * Validates the request token before fetching draft sales orders and returning them
     * as a collection of MonaalSoResource.
     * 
     * @param Request $request Incoming request containing the 'token' to authenticate.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with the status, message,
     *         and data (a collection of sales orders).
     */
    public function sendSos(Request $request)
    {
        // Validates the incoming request for a 'token'.
        $request->validate([
            'token' => 'required|string|max:255',
        ]);

        // Checks if the provided token matches the expected value.
        if ($request->input('token') != config('monaal.token')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token',
            ]);
        }

        // Fetches draft sales orders and their items.
        $data = Po::where('status', 'draft')->with('poItems')->get();

        // Returns the sales orders as a collection of resources.
        return response()->json([
            'data' => MonaalSoResource::collection($data),
            'status' => 'success',
            'message' => 'Synced successfully',
        ]);
        
    }

    /**
     * Synchronizes party information from an external system into the application.
     * Validates the request token before fetching parties of type 'product-factory' and returning them
     * as a collection of MonaalCustomerResource.
     * 
     * @param Request $request Incoming request containing the 'token' to authenticate.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with the status, message,
     *         and data (a collection of party information).
     */
    public function sendParties(Request $request)
    {
        // Validates the incoming request for a 'token'.
        $request->validate([
            'token' => 'required|string|max:255',
        ]);

        // Checks if the provided token matches the expected value.
        if ($request->input('token') != config('monaal.token')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token',
            ]);
        }

        // Fetches parties of type 'product-factory'.
        $data = Party::where('type', 'product-factory')->get();

        // Returns the parties as a collection of resources.
        return response()->json([
            'data' => MonaalCustomerResource::collection($data),
            'status' => 'success',
            'message' => 'Synced successfully',
        ]);    
    }

}
