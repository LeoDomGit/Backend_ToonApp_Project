<?php

namespace App\Http\Controllers;

use App\Models\FeatureImage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
class FeatureImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data=FeatureImage::all();
        return Inertia::render('Features/Image',['datafeatures'=>$data]);
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
        $validator = Validator::make($request->all(), [
            'images.*' => 'required|file|mimes:png,jpg,jpeg|max:2048', // Validate each file
            'api_route'=>'required|unique:feature_images,api_route'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('public/apifeatures', $filename);
                $background = FeatureImage::create([
                    'path' => 'apifeatures/' . $filename,
                    'api_route'=>$request->api_route
                ]);
            }
        }
        $data = FeatureImage::all();
        return response()->json([
            'check'=>true,
            'msg' => 'API Image uploaded successfully.',
            'data' => $data
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(FeatureImage $featureImage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FeatureImage $featureImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'api_route' => 'sometimes|required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }
        $featureImage=FeatureImage::find($id);
        if(!$featureImage){
            return response()->json(['check'=>false,'msg'=>'Không tìm thấy loại size hình ảnh']);
        }
        $data= $request->all();
        $data['updated_at'] = now();
        $featureImage->update($data);
        $data = FeatureImage::all();
        return response()->json([
            'check'=>true,
            'msg' => 'Background images uploaded successfully.',
            'data' => $data
        ], 200);
    }
    public function feature_update_image($id){
        $result = FeatureImage::where('id', $id)->first();
        if(!$result){
            return response()->json(['check'=>false,'msg'=>'Không tìm thấy feature']);
        }
        if(!request()->hasFile('image')){
            return response()->json(['check'=>false,'msg'=>'Vui lòng chọn hình ảnh']);
        }
        $filePath = storage_path('public/apifeatures' .$result->image );
        if (file_exists($filePath)) {
            unlink($filePath); 
        }
        $image= request()->file('image');
        $path = $image->storeAs('public/apifeatures', $image->getClientOriginalName());
        $data['path'] = 'apifeatures/'.$image->getClientOriginalName();
        $data['updated_at']=now();
        FeatureImage::where('id', $id)->update($data);
        $data= FeatureImage::all();
        return response()->json(['check'=>true,'data'=>$data]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeatureImage $featureImage)
    {
        //
    }
}
