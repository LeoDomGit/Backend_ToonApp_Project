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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        $id =$this->route('feature');
        if ($this->isMethod('POST')) {
            return [
               'name'=>'required',
               'duration'=>'required|numeric',
               'price'=>'required|numeric',
               'description'=>'required',
            ];
        } else if ($this->isMethod('put') || $this->isMethod('patch')) {
            $item = SubcriptionPackage::where('id',$id)->first();
            if (!$item) {
                throw new HttpResponseException(response()->json([
                    'check' => false,
                    'msg'   => 'Subcription not found '.$id,
                ], 200)); 
            }
            return [];
        }else if ($this->isMethod('delete')) {
            $item = SubcriptionPackage::find($id);
            if (!$item) {
                throw new HttpResponseException(response()->json([
                    'check' => false,
                    'msg'   => 'Subcription not found',
                ], 200)); 
            }
            return [];
        }
        return [];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'check' => false,
            'msg'  => $validator->errors()->first(),
            'errors'=>$validator->errors(),
        ], 200));
    }
}
