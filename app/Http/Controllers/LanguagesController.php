<?php

namespace App\Http\Controllers;

use App\Http\Requests\LanguageRequest;
use App\Models\Languages;
use App\Traits\HasCrud;
use Illuminate\Http\Request;

class LanguagesController extends Controller
{
    use HasCrud;

    public function __construct()
    {
        $this->model=Languages::class;
        $this->view='Languages/Index';
        $this->data=['languages'=>$this->model::all()];

    }

    // This will use the UserRequest for validation
    public function update(LanguageRequest $request, $id)
    {
       $data=$request->all();
       $data['updated_at']=now();
        $this->model::find($id)->update($data);
        $data=$this->model::all();
        return response()->json(['check'=>true,'data'=>$data]);
    }
}
