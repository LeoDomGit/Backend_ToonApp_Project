<?php

namespace App\Http\Controllers;

use App\Http\Requests\LanguageListRequest;
use App\Models\LanguageList;
use App\Traits\HasCrud;
use Illuminate\Http\Request;

class LanguagesListController extends Controller
{
    use HasCrud;

    public function __construct()
    {
        $this->model=LanguageList::class;
        $this->view='LanguageList/Index';
        $this->data=['languages'=>$this->model::all()];

    }

    // This will use the UserRequest for validation
    public function update(LanguageListRequest $request, $id)
    {
       $data=$request->all();
       $data['updated_at']=now();
        $this->model::find($id)->update($data);
        $data=$this->model::all();
        return response()->json(['check'=>true,'data'=>$data]);
    }
}
