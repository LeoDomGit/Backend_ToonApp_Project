<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeatureRequest;
use App\Models\Features;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeaturesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $features =Features::all();
        return Inertia::render('Features/Index',['datafeatures'=>$features]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FeatureRequest $request)
    {
        $data = $request->all();
        $data['created_at']= now();
        Features::create($data);
        $data =Features::all();
        return response()->json(['check'=>true,'data'=>$data]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Features $features)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function api_index(Features $features)
    {
        $features =Features::with('subFeatures')->get();
        return response()->json($features);
    }
    public function api_detail(Features $features,$id)
    {
        $features =Features::with('subFeatures')->where('id',$id)->first();
        return response()->json($features);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(FeatureRequest $request, $id)
    {
        $data = $request->all();
        $data['updated_at']= now();
        Features::where('id',$id)->update($data);
        $data =Features::all();
        return response()->json(['check'=>true,'data'=>$data]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeatureRequest $features,$id)
    {
        Features::where('id',$id)->delete();
        $data =Features::all();
        return response()->json(['check'=>true,'data'=>$data]);
    }
}
