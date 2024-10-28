<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeatureRequest;
use App\Http\Requests\SubFeatureRequest;
use App\Models\Features;
use App\Models\SubFeatures;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubFeaturesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data =SubFeatures::with('feature')->get();
        $features = Features::all();
        return Inertia::render('Features/SubFeatures',['dataSubFeatures'=>$data,'dataFeatures'=>$features]);
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
    public function store(SubFeatureRequest $request)
    {
        $data = $request->all();
        $data['created_at']= now();
        SubFeatures::create($data);
        $data =SubFeatures::with('feature')->get();
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
    public function edit(Features $features)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SubFeatureRequest $request, $id)
    {
        $data = $request->all();
        $data['updated_at']= now();
        SubFeatures::where('id',$id)->update($data);
        $data =SubFeatures::with('feature')->get();
        return response()->json(['check'=>true,'data'=>$data]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeatureRequest $features,$id)
    {
        SubFeatures::where('id',$id)->delete();
        $data =SubFeatures::with('feature')->get();
        return response()->json(['check'=>true,'data'=>$data]);
    }
}
