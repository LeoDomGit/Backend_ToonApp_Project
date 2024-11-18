<?php

namespace App\Http\Controllers;

use App\Models\ImageSize;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
class ImageSizeController extends Controller
{
   /**
     * Display a listing of the resource.
     */
     public function index()
    {
        $sizes= ImageSize::all();
        return Inertia::render("ImageSize/Index",['sizes'=>$sizes]);
    }

    public function getAll(){
        $sizes= ImageSize::all();
        return response()->json(['check'=> true,'data'=> $sizes]);
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
            $path = $request->file('image')->store('sizes', 'public');
        }
        $validator = Validator::make($request->all(), [
            'size' => 'required|unique:image_sizes,size',

        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $size = ImageSize::create([
            'size' => $request->input('size'),
            'width'=> $request->input('width'),
            'height' => $request->input('height'),
            'status' => 0,
            'image' => $path,
        ]);

        return response()->json(['check'=> true,'data'=> $size]);
    }

    /**
     * Display the specified resource.
     */
    public function api_index(ImageSize $ImageSize)
    {
        $sizes= ImageSize::active()->get();
        return response()->json($sizes);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ImageSize $ImageSize)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ImageSize $ImageSize,$id)
    {
        $validator = Validator::make($request->all(), [
            'size' => 'unique:image_sizes,size',

        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $size = ImageSize::find($id);
        if(!$size){
            return response()->json(['check'=>false,'msg'=>'Không tìm thấy loại size hình ảnh']);
        }
        $data = $request->all();
        ImageSize::where('id',$id)->update($data);
        $sizes= ImageSize::all();
        return response()->json(['check'=> true,'data'=> $sizes]);

    }

    public function update_image($id)
    {
        $result = ImageSize::where('id', $id)->first();
        if(!$result){
            return response()->json(['check' => false, 'msg' => 'không tìm thấy size']);
        }
        if(!request()->hasFile('image')){
            return response()->json(['check' => false, 'msg' => 'Vui lòng chọn hình ảnh']);
        }
        $filePath = storage_path('public/sizes'.$result->image);
        if(file_exists($filePath)){
            unlink($filePath);
        }
        $image = request()->file('image');
        $path = $image->storeAs('public/sizes', $image->getClientOriginalName());
        $data['image'] = 'sizes/' . $image->getClientOriginalName();
        $data['updated_at'] = now();
        ImageSize::where('id', $id)->update($data);
        $data=ImageSize::all();
        return response()->json(['check'=>true, 'data'=>$data]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ImageSize $ImageSize,$id)
    {
        $size = ImageSize::find($id);
        if(!$size){
            return response()->json(['check'=>false,'msg'=>'Không tìm thấy loại tài khoản']);
        }
        $size->delete();
        $sizes=ImageSize::all();
        return response()->json(['check'=>true,'data'=>$sizes]);
    }
}
