<?php

namespace App\Http\Controllers;

use App\Models\AiImageCartoonizer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiImageCartoonizerController extends Controller
{

    public function index()
    {
        $cartoonizers = AiImageCartoonizer::all(); // Lấy tất cả các bản ghi
        return Inertia::render('Aiimagecartoon/Index', ['data' => $cartoonizers]);
    }


    public function store(Request $request)
    {
        $validator = $request->validate([
            'model_name' => 'required|string',
            'prompt' => 'nullable|string|max:140',
            'overwrite' => 'required|boolean',
            'denoising_strength' => 'required|numeric|between:0,1',
            'image_uid' => 'required|string',
            'cn_name' => 'required|string'
        ]);

        $cartoonizer = AiImageCartoonizer::create($validator);

        return response()->json(['message' => 'Cartoonizer created successfully', 'data' => $cartoonizer], 201);
    }

    // Hiển thị một cartoonizer cụ thể
    public function show($id)
    {
        $cartoonizer = AiImageCartoonizer::find($id);

        if (!$cartoonizer) {
            return response()->json(['error' => 'Cartoonizer not found'], 404);
        }

        return response()->json(['cartoonizer' => $cartoonizer]);
    }

    // Cập nhật một cartoonizer
    public function update(Request $request, $id)
    {
        $cartoonizer = AiImageCartoonizer::find($id);

        if (!$cartoonizer) {
            return response()->json(['error' => 'Cartoonizer not found'], 404);
        }

        $validator = $request->validate([
            'model_name' => 'required|string',
            'prompt' => 'nullable|string|max:140',
            'overwrite' => 'required|boolean',
            'denoising_strength' => 'required|numeric|between:0,1',
            'image_uid' => 'required|string',
            'cn_name' => 'required|string'
        ]);

        $cartoonizer->update($validator);

        return response()->json(['message' => 'Cartoonizer updated successfully', 'data' => $cartoonizer]);
    }

    // Xóa một cartoonizer
    public function destroy($id)
    {
        $cartoonizer = AiImageCartoonizer::find($id);

        if (!$cartoonizer) {
            return response()->json(['error' => 'Cartoonizer not found'], 404);
        }

        $cartoonizer->delete();

        return response()->json(['message' => 'Cartoonizer deleted successfully']);
    }
}
