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

        // Get the field and value from the request
        $field = $request->input('field');
        $value = $request->input('value');

        // Ensure that the field to be updated is allowed to be modified (for security reasons)
        $allowedFields = ['model_name', 'prompt', 'overwrite', 'denoising_strength', 'image_uid', 'cn_name', 'apiKey', 'trans_id'];

        if (!in_array($field, $allowedFields)) {
            return response()->json([
                'check' => false,
                'msg' => 'Invalid field provided.',
            ], 400);
        }

        // Validate the value of the field
        // You can add specific validation for each field if needed (e.g., validate 'overwrite' as boolean, 'denoising_strength' as numeric)
        $validatedData = $request->validate([
            'field' => 'required|string|in:' . implode(',', $allowedFields), // Ensure it's a valid field
            'value' => 'required', // Make sure the value is not empty
        ]);

        // Update only the specific field
        $cartoonizer->$field = $value;

        try {
            // Save the changes to the database
            $cartoonizer->save();

            // Return success response with the updated data
            return response()->json([
                'check' => true,
                'msg' => 'Cartoonizer updated successfully.',
                'data' => $cartoonizer,  // Include the updated cartoonizer data
            ]);
        } catch (\Exception $e) {
            // Handle any exception that might occur during the update process
            return response()->json([
                'check' => false,
                'msg' => 'Failed to update cartoonizer.',
                'details' => $e->getMessage(),  // Provide exception details for debugging purposes
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
