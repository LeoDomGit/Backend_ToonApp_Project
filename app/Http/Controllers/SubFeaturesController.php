<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubFeatureRequest;
use App\Models\Features;
use App\Models\SubFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SubFeaturesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = SubFeatures::with('feature')->get();
        $features = Features::all();
        return Inertia::render('Features/SubFeatures', ['dataSubFeatures' => $data, 'dataFeatures' => $features]);
    }
    public function sub_feature_update_image($id){
        $result = SubFeatures::where('id', $id)->first();
        if(!$result){
            return response()->json(['check'=>false,'msg'=>'Không tìm thấy feature']);
        }
        if(!request()->hasFile('image')){
            return response()->json(['check'=>false,'msg'=>'Vui lòng chọn hình ảnh']);
        }
        $filePath = storage_path('public/features' .$result->image );
        if (file_exists($filePath)) {
            unlink($filePath); 
        }
        $image= request()->file('image');
        $path = $image->storeAs('public/features', $image->getClientOriginalName());
        $data['image'] = 'features/'.$image->getClientOriginalName();
        $data['updated_at']=now();
        SubFeatures::where('id', $id)->update($data);
        $data= SubFeatures::with('feature')->get();
        return response()->json(['check'=>true,'data'=>$data]);
    }
    /**
     * Show the form for creating a new resource.
     */

     public function update_feature_slug(){
        $result = SubFeatures::all();
        foreach ($result as $key => $item) {
            $item->update(attributes: ['slug' => Str::slug($item->name)]);
        }
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
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'feature_id' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $subFeature = new SubFeatures();
        $subFeature->name = $request->name;
        $subFeature->description = $request->description;
        $subFeature->feature_id = $request->feature_id;
        $subFeature->slug= Str::slug($request->name);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('sub_feature_images', 'public');
            $subFeature->image = $path; // Save the path to the database
        }

        $subFeature->save();

        return response()->json(['check' => true, 'data' => SubFeatures::all()]);
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
        if($request->has('name')){
            $data['slug']= Str::slug($request->name);
        }
        $data['updated_at'] = now();
        SubFeatures::where('id', $id)->update($data);
        $data = SubFeatures::with('feature')->get();
        return response()->json(['check' => true, 'data' => $data]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubFeatureRequest $features, $id)
    {
        SubFeatures::where('id', $id)->delete();
        $data = SubFeatures::with('feature')->get();
        return response()->json(['check' => true, 'data' => $data]);
    }
}
