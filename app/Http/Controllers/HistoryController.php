<?php

namespace App\Http\Controllers;

use App\Models\History;
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
            'customer_id' => 'required|string',
            'image_result' => 'required|string',
            'device_id' => 'nullable|string', // Optional device_id
        ]);

        // If validation fails, return errors
        if ($validator->fails()) {
            return response()->json([
                'check' => false,
                'msg' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if device_id is provided
        if ($request->has('device_id')) {
            // Find the customer based on the device_id
            $customerData = Customers::getCustomerByDeviceId($request->device_id);

            // If customer data exists, use it; otherwise, return an error
            if ($customerData) {
                $customer_id = $customerData['customer_id'];
                $image_result = $customerData['image_result'];
            } else {
                return response()->json([
                    'check' => false,
                    'msg' => 'No customer found for the provided device_id.',
                ], 404);
            }
        } else {
            // If no device_id, use the customer_id and image_result from the request
            $customer_id = $request->customer_id;
            $image_result = $request->image_result;
        }

        // Create the history record
        $history = History::create([
            'customer_id' => $customer_id,
            'image_result' => $image_result,
        ]);

        return response()->json([
            'check' => true,
            'msg' => 'History created successfully',
            'data' => $history,
        ], 201);
    }

    // Update an existing history record
    public function update(Request $request, $id)
    {
        $history = History::find($id);

        if (!$history) {
            return response()->json([
                'check' => false,
                'msg' => 'History not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string',
            'image_result' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'check' => false,
                'msg' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $history->update([
            'customer_id' => $request->customer_id,
            'image_result' => $request->image_result,
        ]);

        return response()->json([
            'check' => true,
            'msg' => 'History updated successfully',
            'data' => $history,
        ]);
    }

    // Delete a history record
    public function destroy($id)
    {
        $history = History::find($id);

        if (!$history) {
            return response()->json([
                'check' => false,
                'msg' => 'History not found',
            ], 404);
        }

        $history->delete();

        return response()->json([
            'check' => true,
            'msg' => 'History deleted successfully',
        ]);
    }
}
