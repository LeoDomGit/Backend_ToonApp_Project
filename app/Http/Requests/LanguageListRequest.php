<?php

namespace App\Http\Requests;

use App\Models\Languages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LanguageListRequest extends FormRequest
{
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
        $id = $this->route('language_list');
        if ($this->isMethod('POST')) {
            return [
               'language'=>'required|unique:languages_list,language',
               'key'=>'required',
            ];
        } else if ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'language'=>'nullable|unique:languages_list,language',
             ];
        }else if ($this->isMethod('delete')) {
            $feature = Languages::find($id);
            if (!$feature) {
                throw new HttpResponseException(response()->json([
                    'check' => false,
                    'msg'   => 'Role id  not found',
                ], 200));
            }
        }
        return [];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'check' => false,
            'msg'  => $validator->errors()->first(),
            'errors'=>$validator->errors(),
            'data'=>Languages::all()
        ], 200));
    }
}
