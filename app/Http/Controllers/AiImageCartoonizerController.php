<?php

namespace App\Http\Controllers;

use App\Models\AiImageCartoonizer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiImageCartoonizerController extends Controller
{
    // Display all cartoonizers
    public function index()
    {
        $cartoonizers = AiImageCartoonizer::all();

        return Inertia::render('APIVance/Index', [
            'data' => $cartoonizers,
            'message' => $cartoonizers->isEmpty() ? 'No cartoonizers found.' : null,
        ]);
    }

    // Store a new cartoonizer
    public function store(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'apiKey' => 'required|string',
            'model_name' => 'required|string',
            'prompt' => 'required|string',
            'overwrite' => 'required|boolean',
            'denoising_strength' => 'required|numeric',
            'image_uid' => 'required|string',
            'cn_name' => 'required|string',
            'trans_id' => 'required|string', // New validation for trans_id
        ]);

        try {
            // Create a new AiImageCartoonizer entry
            $cartoonizer = AiImageCartoonizer::create($validated);

            // Return a success response with the created entry and a check value
            return response()->json([
                'check' => true,
                'msg' => 'Cartoonizer created successfully.',
                'data' => $cartoonizer,  // Include the newly created entry in the response
            ], 201); // 201 status code indicates that the resource was created
        } catch (\Exception $e) {
            // Return a failure response with error message and details
            return response()->json([
                'check' => false,
                'msg' => 'Failed to create cartoonizer.',
                'details' => $e->getMessage(),
            ], 500); // 500 status code indicates a server error
        }
    }

    // Update an existing cartoonizer
    public function update(Request $request, $id)
    {
        // Find the cartoonizer entry by ID
        $cartoonizer = AiImageCartoonizer::find($id);

        // Return an error if the cartoonizer is not found
        if (!$cartoonizer) {
            return response()->json(['check' => false, 'msg' => 'Cartoonizer not found.'], 404);
        }

        // Validate the incoming request data
        $validated = $request->validate([
            'model_name' => 'required|string',
            'prompt' => 'nullable|string|max:140',
            'overwrite' => 'required|boolean',  // Ensure overwrite is a boolean
            'denoising_strength' => 'required|numeric|between:0,1',  // Denoising strength must be between 0 and 1
            'image_uid' => 'required|string',
            'cn_name' => 'required|string',
            'apiKey' => 'nullable|string|max:255',
            'trans_id' => 'nullable|string', // Allow trans_id to be nullable in the update method
        ]);

        // Handle the 'overwrite' field specifically to ensure it's treated correctly
        $validated['overwrite'] = (bool) $validated['overwrite'];  // Convert to boolean value (true or false)

        // Attempt to update the cartoonizer with the validated data
        try {
            $cartoonizer->update($validated);

            // Return success response if the update is successful
            return response()->json([
                'check' => true,
                'msg' => 'Cartoonizer updated successfully.',
                'data' => $cartoonizer  // Optionally return the updated object for further use
            ]);
        } catch (\Exception $e) {
            // Handle any exception that might occur during the update process
            return response()->json([
                'check' => false,
                'msg' => 'Failed to update cartoonizer.',
                'details' => $e->getMessage()  // Provide exception details for debugging purposes
            ], 500);
        }
    }

    // Delete a cartoonizer
    public function destroy($id)
    {
        $cartoonizer = AiImageCartoonizer::find($id);

        if (!$cartoonizer) {
            return response()->json(['check' => false, 'msg' => 'Cartoonizer not found.'], 404);
        }

        try {
            $cartoonizer->delete();
            return response()->json(['check' => true, 'msg' => 'Cartoonizer deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'check' => false,
                'msg' => 'An error occurred while deleting the cartoonizer.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
