<?php

namespace App\Http\Controllers;

use App\Models\Background;
use App\Models\Features;
use App\Models\GroupBackground;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use ZipArchive;
use Illuminate\Support\Facades\Validator;

class BackgroundController extends Controller
{

    public function uploadAndUnzip(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'zip_file' => 'required|file|mimes:zip|max:2048',
            'feature_id' => 'required|exists:features,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }
        // Store the uploaded ZIP file temporarily
        $zipFilePath = $request->file('zip_file')->store('temp');

        // Initialize ZipArchive
        $zip = new ZipArchive;
        if ($zip->open(storage_path('app/' . $zipFilePath)) === TRUE) {
            // Create a directory to extract files
            $extractPath = storage_path('app/public/background/');
            if (!is_dir($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            // Extract the ZIP file
            $zip->extractTo($extractPath);
            $zip->close();

            // Scan the extracted directory and save each file path into the database
            $files = scandir($extractPath);
            foreach ($files as $file) {
                // Skip '.' and '..' entries
                if ($file !== '.' && $file !== '..') {
                    // Create a new Background record
                    Background::create([
                        'path' => 'background/' . $file,
                        'feature_id' => $request->feature_id
                    ]);
                }
            }

            return response()->json(['msg' => 'Files unzipped and paths saved successfully.'], 200);
        } else {
            return response()->json(['msg' => 'Failed to open the ZIP file.'], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $backgrounds = Background::all();
        $features = Features::all();
        return Inertia::render('Background/Index', ['data_images' => [], 'data_features' => $features]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images.*' => 'required|file|mimes:png,jpg,jpeg,webp|max:2048', // Validate each file
            'feature_id' => 'required|exists:features,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('public/background', $filename);
                $background = Background::create([
                    'path' => 'background/' . $filename,
                    'feature_id' => $request->feature_id
                ]);
            }
        }
        $data = Background::where('feature_id', $request->feature_id)->get();
        return response()->json([
            'check' => true,
            'msg' => 'Background images uploaded successfully.',
            'data' => $data
        ], 200);
    }
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }

        $image = $request->file('image');
        $filename = time() . '_' . $image->getClientOriginalName();
        $path = $image->storeAs('public/background', $filename);

        $background = Background::create([
            'path' => 'background/' . $filename,
        ]);

        return response()->json(['check' => true, 'data' => $background], 200);
    }

    public function addToGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image_ids' => 'required|array',
            'image_ids.*' => 'exists:background,id',
            'group_id' => 'required|exists:group_backgrounds,group_id', // Đảm bảo kiểm tra tồn tại trong bảng nhóm
        ]);

        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }

        Background::whereIn('id', $request->image_ids)->update([
            'group_id' => $request->group_id,
        ]);

        return response()->json(['check' => true, 'msg' => 'Images added to group successfully.']);
    }
    public function addImageToGroup(Request $request)
    {
        $request->validate([
            'image_id' => 'required|exists:background,id',
            'group_name' => 'required|string|exists:group_backgrounds,name',
        ]);

        try {
            // Tìm nhóm theo tên
            $group = GroupBackground::where('name', $request->group_name)->first();

            if (!$group) {
                return response()->json(['status' => false, 'message' => 'Group not found'], 404);
            }

            // Tìm ảnh theo ID
            $image = Background::find($request->image_id);

            // Gán ảnh vào nhóm
            $image->group_id = $group->id;
            $image->save();

            return response()->json(['status' => true, 'message' => 'Image added to group successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error adding image to group', 'error' => $e->getMessage()], 500);
        }
    }


    // Lấy ảnh theo nhóm
    public function getImagesByGroup(Request $request)
    {
        $validatedData = $request->validate([
            'group_id' => 'required|exists:group_backgrounds,id', // group_id phải tồn tại
        ]);

        $images = Background::where('group_id', $validatedData['group_id'])->get();

        return response()->json($images);
    }
    public function addImagesToGroup(Request $request)
    {
        $validated = $request->validate([
            'image_ids' => 'required|array', // Các ID ảnh cần thêm
            'group_name' => 'required|string', // Tên nhóm
            'feature_id' => 'required|integer', // ID của feature
        ]);

        try {
            // Tìm hoặc tạo nhóm dựa trên tên nhóm
            $group = GroupBackground::firstOrCreate(
                ['name' => $validated['group_name']],
                ['feature_id' => $validated['feature_id']]
            );

            // Thêm các ảnh vào nhóm bằng cách cập nhật trường group_id
            foreach ($validated['image_ids'] as $imageId) {
                Background::where('id', $imageId)
                    ->update(['group_id' => $group->id]);
            }

            return response()->json(['status' => true, 'message' => 'Images added to group successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error adding images to group.', 'error' => $e->getMessage()], 500);
        }
    }
    // Trong App\Http\Controllers\BackgroundController.php
    public function assignToGroup(Request $request)
    {
        $validatedData = $request->validate([
            'image_ids' => 'required|array|min:1',
            'image_ids.*' => 'exists:background,id', // Kiểm tra từng ID ảnh
            'group_id' => 'required|exists:group_backgrounds,id',
        ]);

        // Cập nhật group_id cho các ảnh được chọn
        Background::whereIn('id', $validatedData['image_ids'])
            ->update(['group_id' => $validatedData['group_id']]);

        return response()->json([
            'message' => 'Images assigned to group successfully!',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $background = Background::where('feature_id', $id)->get();
        return response()->json($background, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Background $background)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'sometimes|required|string|max:255',
            'feature_id' => 'required|exists:features,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }
        $data = $request->all();
        $data['updated_at'] = now();
        $background->update($data);
        $data = Background::where('feature_id', $request->feature_id)->get();
        return response()->json([
            'check' => true,
            'msg' => 'Background images uploaded successfully.',
            'data' => $data
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = Background::find($id);
        $feature_id = $item->feature_id;
        if (!$item) {
            return response()->json(['check' => false, 'msg' => 'Background image not found.'], 200);
        }
        $background = Background::where('id', $id)->first();
        $filePath = storage_path('app/public/' . $background->path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        Background::where('id', $id)->delete();
        $data = Background::where('feature_id', $feature_id)->get();
        return response()->json([
            'check' => true,
            'msg' => 'Background images uploaded successfully.',
            'data' => $data
        ], 200);
    }

    public function api_index()
    {
        $backgrounds = Background::all();
        return response()->json($backgrounds);
    }
}
