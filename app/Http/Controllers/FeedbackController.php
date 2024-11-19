<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    // Hiển thị tất cả feedback
    public function index()
    {
        $feedbacks = Feedback::all(); // Lấy tất cả feedback
        return Inertia::render('Feedback/Index', ['data' => $feedbacks]);
    }

    // Hiển thị chi tiết một feedback
    public function show($id)
    {
        $feedback = Feedback::find($id); // Lấy feedback theo ID
        if (!$feedback) {
            return response()->json(['message' => 'Feedback not found'], 404);
        }
        return response()->json($feedback);
    }

    // Tạo mới một feedback
    public function store(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'flatfom' => 'nullable|string|max:255',
            'feedback' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }
        $data=$request->all();
        $data['created_at']= now();
        $feedback = Feedback::create($data);
        return response()->json([
            'status' => 'success',
            'message' => 'Feedback created successfully',
        ], 201);
    }

    // Cập nhật feedback
    public function update(Request $request, $id)
    {
        $feedback = Feedback::find($id);

        if (!$feedback) {
            return response()->json(['message' => 'Feedback not found'], 404);
        }

        // Validate dữ liệu đầu vào
        $request->validate([
            'device_id' => 'required|string|max:255',
            'flatfom' => 'nullable|string|max:255',
            'feedback' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
        ]);

        // Cập nhật feedback
        $feedback->update($request->all());

        return response()->json([
            'message' => 'Feedback updated successfully',
            'feedback' => $feedback
        ]);
    }

    // Xóa feedback
    public function destroy($id)
    {
        $feedback = Feedback::find($id);

        if (!$feedback) {
            return response()->json(['message' => 'Feedback not found'], 404);
        }

        // Xóa feedback
        $feedback->delete();

        return response()->json(['message' => 'Feedback deleted successfully']);
    }
}
