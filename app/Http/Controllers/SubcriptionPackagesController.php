<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackageRequest;
use App\Models\SubcriptionPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SubcriptionPackagesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subcriptionPackages = SubcriptionPackage::all();
        return Inertia::render('Packages/Index', ['data' => $subcriptionPackages]);
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PackageRequest $request)
    {
        if($request->validated()){
            $data=$request->all();
            $data['created_at']=now();
            SubcriptionPackage::create($data);
            $data= SubcriptionPackage::all();
            return response()->json(['check'=>true,'data'=>$data]);

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SubcriptionPackage $subcriptionPackage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubcriptionPackage $subcriptionPackage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PackageRequest $request, $id)
    {
        $data=$request->all();
            $data['updated_at']=now();
            SubcriptionPackage::where('id',$id)->update($data);
            $data= SubcriptionPackage::all();
            return response()->json(['check'=>true,'data'=>$data]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubcriptionPackage $subcriptionPackage,$id)
    {
        SubcriptionPackage::where('id', $id)->delete();
        $data = SubcriptionPackage::all();
        return response()->json(['check' => true, 'data' => $data]);
    }
}
