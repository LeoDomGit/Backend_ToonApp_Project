<?php

namespace App\Http\Requests;

use App\Models\SubcriptionPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true; // Cho phép tất cả người dùng gửi yêu cầu
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        $id = $this->route('package'); // Lấy ID từ route nếu có

        // Quy tắc cho phương thức POST (tạo mới)
        if ($this->isMethod('POST')) {
            return [
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'duration' => 'required|integer',  // Kiểm tra duration là một số nguyên
                'description' => 'required|string|max:500',
                'status' => 'required|string|in:active,inactive',  // Trạng thái hợp lệ là active hoặc inactive
                'payment_method' => 'required|string|in:credit_card,bank_transfer', // Phương thức thanh toán hợp lệ
            ];
        }

        // Quy tắc cho phương thức PUT/PATCH (cập nhật)
        elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            if (!$id) {
                throw new HttpResponseException(response()->json([
                    'check' => false,
                    'msg' => 'Subscription ID not provided.',
                ], 404));
            }

            $item = SubcriptionPackage::find($id);
            if (!$item) {
                throw new HttpResponseException(response()->json([
                    'check' => false,
                    'msg' => 'Subscription not found with ID ' . $id,
                ], 404));
            }

            return [
                'name' => 'sometimes|required|string|max:255',
                'price' => 'sometimes|required|numeric|min:0',
                'duration' => 'sometimes|required|integer',
                'description' => 'sometimes|required|string|max:500',
                'status' => 'sometimes|required|string|in:active,inactive',
                'payment_method' => 'sometimes|required|string|in:credit_card,bank_transfer',
            ];
        }

        return [];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @return void
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'check' => false,
            'msg' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422)); // Trả về mã lỗi 422 khi validation thất bại
    }
}
