<?php

namespace App\Http\Controllers;

use App\Models\Configs;
use Config;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
  /**
     * Display a listing of the resource.
     */
    public function api_index(){
        $data= Config::active()->get();
        return response()->json($data);
    }
    public function index()
    {
        $data = Configs::all();
        return Inertia::render('Configs/Index', ['dataConfigs' => $data]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'domain' => 'required|string|unique:configs,domain',
            'package_name' => 'required|string|unique:configs,package_name',
        ]);

        // If validation fails, return an error response
        if ($validator->fails()) {
            return response()->json([
                'check' => false,
                'msg' => $validator->errors()->first()
            ], 400);
        }

        // Create the new key
        $key = Configs::create([
            'domain' => $request->domain,
            'package_name' => $request->package_name,
        ]);
        $data = Configs::all();
        return response()->json([
            'check' => true,
            'data' => $data
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate only the fields that are passed (nullable fields)
        $validatedData = $request->validate([
            'domain' => 'nullable|string|',
            'package_name' => 'nullable|string|',
            'status' => 'nullable',
        ]);

        $item=Configs::find($id);
        if ($request->has('domain')) {
            $item->domain = $request->domain;
        }
        if ($request->has('package_name')) {
            $item->package_name = $request->package_name;
        }
        if ($request->has('status')) {
            $item->status = $request->status;
        }
        // Save the updated key
        $item->save();
        $data = Configs::all();
        return response()->json([
            'check' => true,
            'data' => $data
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = Configs::find($id);
        if(!$item){
            return response()->json([
                'check' => false,
                'msg' => 'Key not found.'
            ]);
        $item->delete();
        $data = Configs::all();
        return response()->json([
            'check' => true,
            'msg' => 'Key deleted successfully.',
            'data'=> Configs::all()
        ], 200);
    }
}
}
