<?php

namespace App\Http\Controllers;

use App\Http\Requests\LanguageListRequest;
use App\Models\LanguageList;
use App\Models\Languages;
use App\Traits\HasCrud;
use Illuminate\Http\Request;

class LanguagesListController extends Controller
{
    use HasCrud;
    protected $model1;
    public function __construct()
    {
        $this->model=LanguageList::class;
        $this->model1=Languages::class;
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

    public function index_api(){
        $data=$this->model::active()->get();
        return response()->json($data);
    }
    public function index_key($id){
        $data=$this->model1::select($id)->get();
        return response()->json($data);
    }
}
