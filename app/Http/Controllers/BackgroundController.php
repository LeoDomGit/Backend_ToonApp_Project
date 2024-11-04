<?php

namespace App\Http\Controllers;

use App\Models\Background;
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
                    ]);
                }
            }

            return response()->json(['message' => 'Files unzipped and paths saved successfully.'], 200);
        } else {
            return response()->json(['message' => 'Failed to open the ZIP file.'], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $backgrounds = Background::all();
        return Inertia::render('Background/Index', ['data' => $backgrounds]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images.*' => 'required|file|mimes:png,jpg,jpeg|max:2048', // Validate each file
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
                ]);
            }
        }
        $data = Background::all();
        return response()->json([
            'message' => 'Background images uploaded successfully.',
            'data' => $data
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Background $background)
    {
        return response()->json($background, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Background $background)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'sometimes|required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }
        $data= $request->all();
        $data['updated_at'] = now();
        $background->update($data);
        return response()->json(['message' => 'Background image updated successfully.', 'data' => $background], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Background $background)
    {
        $filePath = storage_path('app/public/' . $background->path);

        if (file_exists($filePath)) {
            unlink($filePath); 
        }
        $background->delete();

        return response()->json(['message' => 'Background image deleted successfully.'], 200);
    }
}
