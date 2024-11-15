<?php

namespace App\Http\Controllers;

use App\Models\Background;
use App\Models\Features;
use App\Models\GroupBackground;
use Illuminate\Http\Request;
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
    // Controller để quản lý nhóm và nền ảnh
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images.*' => 'required|file|mimes:png,jpg,jpeg,webp|max:2048', // Validate each file
            'feature_id' => 'required|exists:features,id',
            'group_backgrounds.*' => 'required|exists:group_backgrounds,name', // Validate group names
        ]);

        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('public/background', $filename);

                // Save the image and associated groups
                Background::create([
                    'path' => 'background/' . $filename,
                    'feature_id' => $request->feature_id,
                    'group_background_id' => implode(",", $request->group_backgrounds), // Save groups as a comma-separated string
                ]);
            }
        }

        $data = Background::where('feature_id', $request->feature_id)
            ->whereIn('group_background_id', $request->group_backgrounds) // Filter by group
            ->get();

        return response()->json(['check' => true, 'data' => $data], 200);
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

    public function api_index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feature_id' => 'required|exists:features,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }
        $backgrounds = GroupBackground::with('backgrounds')->where('feature_id', $request->feature_id)->get();
        return response()->json($backgrounds);
    }

    public function api_single(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'feature_id' => 'required|exists:features,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }
        $backgrounds = GroupBackground::with('backgrounds')
            ->where('feature_id', $request->feature_id)
            ->whereHas('backgrounds', function ($query) use ($id) {
                $query->where('group_id', $id); // Using the correct foreign key
            })
            ->get();
        return response()->json($backgrounds);
    }
}
