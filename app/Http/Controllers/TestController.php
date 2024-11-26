<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CloudflareService;
use Illuminate\Support\Facades\Config;
class TestController extends Controller
{
    protected $cloudflareService;

    public function __construct(CloudflareService $cloudflareService)
    {
        $this->cloudflareService = $cloudflareService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response(Config::get('app.api_key'));
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
    public function store(Request $request)
    {
        if(!$request->file('image')){
            return response()->json(['check'=>false,'msg'=>'no image']);
        }
        $imageFile=$request->file('image');
        $folder='Test';
        $filename='TestFile.png';
        $cdnUrl = $this->cloudflareService->uploadToCloudFlareFromFile($imageFile, $folder, $filename);
        return response()->json(['check'=>true,'url'=>$cdnUrl])
    ;}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
