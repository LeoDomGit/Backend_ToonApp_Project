<?php

namespace App\Http\Controllers;

use App\Models\Background;
use App\Models\GroupBackground;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GroupBackgroundController extends Controller
{
    /**
     * Save the group backgrounds.
     */

    public function saveGroupBackgrounds(Request $request)
    {
        try {
            // Validate incoming request
            $request->validate([
                'group_backgrounds' => 'required|array|min:1',
                'group_backgrounds.*' => 'string|max:255',
                'feature_id' => 'required|exists:features,id',
            ]);

            $createdGroups = [];

            // Loop through each group and save it
            foreach ($request->group_backgrounds as $groupName) {
                // Check if the group already exists
                $existingGroup = GroupBackground::where('name', $groupName)
                    ->where('feature_id', $request->feature_id)
                    ->first();

                // If the group does not exist, create it
                if (!$existingGroup) {
                    $group = GroupBackground::create([
                        'name' => $groupName,
                        'slug' => Str::slug($groupName),
                        'feature_id' => $request->feature_id,
                        'status' => 1,
                    ]);

                    $createdGroups[] = $group; // Add the created group to the response
                } else {
                    $createdGroups[] = $existingGroup; // Add the existing group to the response
                }
            }

            // Return success message with the created or existing groups
            return response()->json([
                'message' => 'Groups added successfully',
                'status' => true,
                'groups' => $createdGroups, // Return the created or existing groups
            ]);
        } catch (ValidationException $e) {
            // Handle validation error and provide details
            return response()->json([
                'message' => 'Validation failed',
                'status' => false,
                'errors' => $e->errors(), // Return detailed validation errors
            ], 422);
        } catch (\Exception $e) {
            // Handle any other errors
            return response()->json([
                'message' => 'Error saving groups',
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getBackgroundImagesByFeatureAndGroup($feature_id, Request $request)
    {
        $group = $request->query('group');
        $query = Background::where('feature_id', $feature_id);

        if ($group) {
            $query->where('group', $group); // filter by group if specified
        }

        $images = $query->get();
        return response()->json($images);
    }

    /**
     * Get backgrounds by feature ID, filtered by selected groups.
     */
    public function getBackgrounds($feature_id)
    {
        // Initialize query to get backgrounds for the given feature ID
        $query = Background::where('feature_id', $feature_id);

        // Check if there are specific group filters and apply them
        if (request()->has('group') && !empty(request()->group)) {
            $groupNames = explode(",", request()->group);

            // Retrieve group IDs based on provided names
            $groupIds = GroupBackground::whereIn('name', $groupNames)
                ->where('feature_id', $feature_id)
                ->pluck('id')
                ->toArray();

            // Filter backgrounds by the selected group IDs
            $query->whereIn('group_background_id', $groupIds);
        }

        // Fetch the filtered backgrounds
        $backgrounds = $query->get();

        return response()->json($backgrounds);
    }
    public function removeGroup(Request $request)
    {
        try {
            // Validate the incoming request to ensure 'group_name' and 'feature_id' are provided
            $request->validate([
                'group_name' => 'required|string|max:255',
                'feature_id' => 'required|exists:features,id',
            ]);

            // Find the group by name and feature_id
            $group = GroupBackground::where('name', $request->group_name)
                ->where('feature_id', $request->feature_id)
                ->first();

            if ($group) {
                // Delete the group from the database
                $group->delete();

                return response()->json([
                    'message' => 'Group removed successfully',
                    'status' => true,
                ]);
            } else {
                return response()->json([
                    'message' => 'Group not found',
                    'status' => false,
                ], 404);
            }
        } catch (\Exception $e) {
            // Handle any errors that occur during the process
            return response()->json([
                'message' => 'Error removing group',
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get groups by feature_id.
     */
    public function getGroupsByFeature($feature_id)
    {
        $groups = GroupBackground::where('feature_id', $feature_id)
            ->where('status', 1)
            ->get();

        return response()->json($groups);
    }
}
