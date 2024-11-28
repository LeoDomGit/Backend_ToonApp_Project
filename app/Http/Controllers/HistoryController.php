<?php

namespace App\Http\Controllers;

use App\Models\Activities;
use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HistoryController extends Controller
{
    // Store new history record
    public function store(Request $request)
    {
        // Validate request input
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string', // Device ID is required
            'platform' => 'required|string',  // Platform is required
        ]);

        // If validation fails, return errors
        if ($validator->fails()) {
            return response()->json([
                'check' => false,
                'msg' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if device_id and platform are provided
        if ($request->has('device_id') && $request->has('platform')) {
            // Find the customer based on device_id and platform
            $customerData = Customers::getCustomerByDeviceIdAndPlatform($request->device_id, $request->platform);

            // If customer data exists, use it; otherwise, return an error
            if ($customerData) {
                $customer_id = $customerData['customer_id'];
                $image_result = $customerData['image_result'];
            } else {
                return response()->json([
                    'check' => false,
                    'msg' => 'No customer found for the provided device_id and platform.',
                ], 404);
            }
        } else {
            return response()->json([
                'check' => false,
                'msg' => 'device_id and platform are required.',
            ], 400);
        }

        // Create the history record
        $history = Activities::create([
            'customer_id' => $customer_id,
            'image_result' => $image_result,
        ]);

        return response()->json([
            'check' => true,
            'msg' => 'History created successfully',
            'data' => $history,
        ], 201);
    }

    // Retrieve customer_id and image_result based on device_id
    public function getCustomerDetails(Request $request)
    {
        // Validate request input
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string', // Device ID is required
        ]);

        // If validation fails, return errors
        if ($validator->fails()) {
            return response()->json([
                'check' => false,
                'msg' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the customer by device_id
        $customer = Customers::where('device_id', $request->input('device_id'))->first();

        if (!$customer) {
            return response()->json([
                'check' => false,
                'msg' => 'No customer found for the provided device_id.',
            ], 404);
        }

        // Retrieve the latest activity for the customer
        $activity = Activities::where('customer_id', $customer->id)->latest()->first();

        if (!$activity) {
            return response()->json([
                'check' => false,
                'msg' => 'No activity found for the customer.',
            ], 404);
        }

        // Return the customer_id and image_result
        return response()->json([
            'check' => true,
            'msg' => 'Customer details retrieved successfully.',
            'data' => [
                'customer_id' => $customer->id,
                'image_result' => $activity->image_result,
            ],
        ], 200);
    }
}
