<?php

namespace App\Http\Controllers;

use App\Services\CloudflareService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Effects;
use Inertia\Inertia;

class EffectsController extends Controller
{
    protected $cloudFlareService;

    public function __construct(CloudflareService $cloudFlareService)
    {
        $this->cloudFlareService = $cloudFlareService;
    }

    public function index()
    {
        $effects = Effects::all();
        return Inertia::render('Effects/Index', ['effects' => $effects]);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|unique:effects,name',
        ]);
        if ($validate->fails()) {
            return response()->json(['check' => false, 'msg' => $validate->errors()->first()]);
        }

        $path = null;
        if ($request->file('image')) {
            $path = $this->cloudFlareService->uploadToCloudFlareFromFile($request->file('image'), 'effects', $request->file('image')->getClientOriginalName());
        }
        $effect = Effects::create([
            'name' => $request->input('name'),
            'slug' => Str::slug($request->input('name')),
            'image' => $path,
            'created_at' => now()
        ]);
        return response()->json(['check' => true, 'data' => $effect]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $queryResult = Effects::where('id', $id)->first();
        if ($queryResult) {
            if ($request->has('name')) {
                $data['slug'] = Str::slug($request->input('name'));
            }
            $data['updated_at'] = now();
            Effects::where('id', $id)->update($data);
            $effects = Effects::all();
            return response()->json(['check' => true, 'data' => $effects]);
        } else {
            return response()->json(['check' => false, 'msg' => 'Effect is not found']);
        }
    }

    public function destroy($id)
    {
        $queryResult = Effects::find($id);
        if ($queryResult) {
            $queryResult->delete();
            $effects = Effects::all();
            return response()->json(['check' => true, 'data' => $effects]);
        } else {
            return response()->json(['check' => false, 'msg' => 'Effect is not found']);
        }
    }

    public function api_index(Request $request)
    {
        $effects = Effects::all();
        return response()->json(['check' => true, 'data' => $effects]);
    }

    public function api_single($id)
    {
        $effect = Effects::where('id', $id)->get();
        if ($effect) {
            return response()->json(['check' => true, 'data' => $effect]);
        } else {
            return response()->json(['check' => false, 'msg' => 'Effect is not found']);
        }
    }

    public function api_update_image(Request $request, $id)
    {
        $data = $request->all;
        $queryResult = Effects::find($id);
        if ($queryResult) {
            if ($request->hasFile('image')) {
                $path = $this->cloudFlareService->uploadToCloudFlareFromFile($request->file('image'), 'effects', $request->file('image')->getClientOriginalName());
                $data['image'] = $path;
                $data['updated_at'] = now();
                Effects::where('id', $id)->update($data);
                $effects = Effects::all();
                return response()->json(['check' => true, 'data' => $effects]);
            } else {
                return response()->json(['check' => false, 'msg' => "Please, select new image"]);
            }
        } else {
            return response()->json(['check' => false, 'msg' => 'Effect is not found']);
        }

    }
}
