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
        $validator = Validator::make($request->all(), [
            'size' => 'required|unique:image_sizes,size',
          
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        ImageSize::create($request->all());
        $sizes= ImageSize::all();
        return response()->json(['check'=> true,'data'=> $sizes]);
    }

    /**
     * Display the specified resource.
     */
    public function api_index(ImageSize $ImageSize)
    {
        
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
        $size = ImageSize::find($id);
        if(!$size){
            return response()->json(['check'=>false,'msg'=>'Không tìm thấy loại size hình ảnh']);
        }
        $data = $request->all();
        ImageSize::where('id',$id)->update($data);
        $sizes= ImageSize::all();
        return response()->json(['check'=> true,'data'=> $sizes]);

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
