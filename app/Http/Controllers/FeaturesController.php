<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeatureRequest;
use App\Models\Features;
use App\Models\ImageSize;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
class FeaturesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $features = Features::all();
        $sizes= ImageSize::all();
        return Inertia::render('Features/Index', ['datafeatures' => $features,'datasizes'=>$sizes]);
    }


    //=============================================================
    public function update_feature_slug(){
        $result = Features::all();
        foreach ($result as $key => $item) {
            $item->update(['slug' => Str::slug($item->name)]);
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

        $path = null;
        if ($request->hasFile('image')) {

            $path = $request->file('image')->store('features', 'public');
        }


        $feature = Features::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'slug'=>Str::slug($request->input('name')),
            'api_endpoint' => $request->input('api_endpoint'),
            'image' => $path,
            'model_id' => $request->input('model_id'),
            'prompt' => $request->input('prompt'),
            'presetStyle' => $request->input('presetStyle'),
            'initImageId' => $request->input('initImageId'),
            'preprocessorId' => $request->input('preprocessorId'),
            'strengthType' => $request->input('strengthType')
        ]);

        return response()->json(['check' => true, 'data' => $feature]);
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
        $features = Features::with(['subFeatures','sizes'])->get();
        return response()->json($features);
    }
    public function api_detail(Features $features, $id)
    {
        $features = Features::with(['subFeatures','sizes'])->where('id', $id)->first();
        return response()->json($features);
    }
    /**
     * Update the specified resource in storage.
     */

     public function updated_size(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'size_id' => 'required|array',
            'size_id.*'=>'exists:image_sizes,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $arr=$request->size_id;
        FeaturesSizes::where('feature_id',$id)->delete();
        foreach ($arr as $key => $value) {
           FeaturesSizes::create([
            'feature_id'=>$id,
            'size_id'=>$value,
            'created_at'=>now()
           ]);
        }
        $data = Features::with('sizes')->get();
        return response()->json(['check'=>true,'data'=>$data]);
    }
    public function update(FeatureRequest $request, $id)
    {
        $data = $request->all();
        if($request->has('name')){
            $data['slug'] = Str::slug($request->input('name')); 
        }
        $data['updated_at'] = now();
        Features::where('id', $id)->update($data);
        $data = Features::all();
        return response()->json(['check' => true, 'data' => $data]);
    }

    public function feature_update_image($id)
    {
        $result = Features::where('id', $id)->first();
        if (!$result) {
            return response()->json(['check' => false, 'msg' => 'Không tìm thấy feature']);
        }
        if (!request()->hasFile('image')) {
            return response()->json(['check' => false, 'msg' => 'Vui lòng chọn hình ảnh']);
        }
        $filePath = storage_path('public/features' . $result->image);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $image = request()->file('image');
        $path = $image->storeAs('public/features', $image->getClientOriginalName());
        $data['image'] = 'features/' . $image->getClientOriginalName();
        $data['updated_at'] = now();
        Features::where('id', $id)->update($data);
        $data = Features::all();
        return response()->json(['check' => true, 'data' => $data]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeatureRequest $features, $id)
    {
        Features::where('id', $id)->delete();
        $data = Features::all();
        return response()->json(['check' => true, 'data' => $data]);
    }
}
