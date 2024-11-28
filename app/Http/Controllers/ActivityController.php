<?php

namespace App\Http\Controllers;

use App\Models\Activities;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityController extends Controller
{
    // Display a listing of the activities
    public function index()
    {
        $activities = Activities::all();  // Get all activities
        return Inertia::render('History/Index', [
            'data' => $activities,
            'message' => $activities->isEmpty() ? 'No cartoonizers found.' : null,
        ]);
    }

    // Show the form for creating a new activity
    public function create()
    {
        // You can return a view here if needed
    }

    // Store a newly created activity in storage
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'photo_id' => 'required|integer',
            'features_id' => 'required|integer',
            'image_result' => 'required|string',
            'attributes' => 'required|string',
            'request' => 'required|string',
            'image_size' => 'required|string',
            'ai_model' => 'required|string',
            'api_endpoint' => 'required|string',
        ]);

        $activity = Activities::create($validated);

        return response()->json($activity, 201);  // Return newly created activity
    }

    // Display the specified activity
    public function show($id)
    {
        $activity = Activities::find($id);

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        return response()->json($activity);
    }

    // Show the form for editing the specified activity
    public function edit($id)
    {
        // You can return a view for editing here if needed
    }

    // Update the specified activity in storage
    public function update(Request $request, $id)
    {
        $activity = Activities::find($id);

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'photo_id' => 'required|integer',
            'features_id' => 'required|integer',
            'image_result' => 'required|string',
            'attributes' => 'required|string',
            'request' => 'required|string',
            'image_size' => 'required|string',
            'ai_model' => 'required|string',
            'api_endpoint' => 'required|string',
        ]);

        $activity->update($validated);

        return response()->json($activity);
    }

    // Remove the specified activity from storage
    public function destroy($id)
    {
        $activity = Activities::find($id);

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        $activity->delete();

        return response()->json(['message' => 'Activity deleted successfully']);
    }
}
