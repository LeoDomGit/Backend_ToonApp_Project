<?php

namespace App\Http\Controllers;


use Inertia\Inertia;
use App\Models\SecretKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SecretKeyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $secretKeys = SecretKey::all();
        return Inertia::render('Secretkey/Index', ['datasecretkeys' => $secretKeys]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
            'secret_key' => 'required|string',
            'is_active' => 'required|boolean', // Ensure is_active is a boolean
        ]);

        // If validation fails, return an error response
        if ($validator->fails()) {
            return response()->json([
                'check' => false,
                'msg' => $validator->errors()->first()
            ], 400);
        }

        // Create the new key
        $secretKeys = SecretKey::create([
            'api_key' => $request->api_key,
            'secret_key' => $request->secret_key,
            'is_active' => $request->is_active, // Save is_active as boolean
        ]);

        return response()->json([
            'check' => true,
            'data' => $secretKeys
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate only necessary fields (nullable)
        $validatedData = $request->validate([
            'api_key' => 'nullable|string|max:255',
            'secret_key' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $secretKey = SecretKey::find($id);

        // Check if the SecretKey exists
        if (!$secretKey) {
            return response()->json([
                'check' => false,
                'message' => 'Secret key not found.'
            ], 404);
        }

        // Update the fields if present in the request
        if ($request->has('api_key')) {
            $secretKey->api_key = $request->api_key;
        }
        if ($request->has('secret_key')) {
            $secretKey->secret_key = $request->secret_key;
        }
        if ($request->has('is_active')) {
            // Ensure we properly convert the status to a boolean
            $secretKey->is_active = $request->is_active === 'on' ? 1 : 0;
        }

        // Save the changes
        $secretKey->save();

        // Return the updated data
        return response()->json([
            'check' => true,
            'data' => $secretKey
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SecretKey $secretKey, $id)
    {
        $secretKey = SecretKey::find($id); // where('','')->first()
        $secretKey->delete();
        $data = SecretKey::all();
        return response()->json([
            'check' => true,
            'msg' => 'Secret key deleted successfully.',
            'data' => $data
        ], 200);
    }
}
