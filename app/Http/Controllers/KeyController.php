<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KeyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $keys = Key::all();
        return Inertia::render('Key/Index', ['datakeys' => $keys]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'api' => 'required|string|',
            'key' => 'required|string|',
        ]);

        // If validation fails, return an error response
        if ($validator->fails()) {
            return response()->json([
                'check' => false,
                'msg' => $validator->errors()->first()
            ], 400);
        }

        // Create the new key
        $key = Key::create([
            'api' => $request->api,
            'key' => $request->key,
        ]);

        return response()->json([
            'check' => true,
            'data' => $key
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Key $key)
    {
        // Validate only the fields that are passed (nullable fields)
        $validatedData = $request->validate([
            'api' => 'nullable|string|',
            'key' => 'nullable|string|',
        ]);

        // Update the key with the new data if present
        if ($request->has('api')) {
            $key->api = $request->api;
        }
        if ($request->has('key')) {
            $key->key = $request->key;
        }

        // Save the updated key
        $key->save();

        return response()->json([
            'check' => true,
            'data' => $key
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Key $key)
    {
        $key->delete();

        return response()->json([
            'check' => true,
            'msg' => 'Key deleted successfully.'
        ], 200);
    }
}
