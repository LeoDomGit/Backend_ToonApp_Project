<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Effects;
use Inertia\Inertia;

class EffectsController extends Controller
{
    public function index()
    {
        $effects = Effects::all();
        return Inertia::render('Effects/Index', ['effects' => $effects]);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|unique:effects,name'
        ]);

        if (!$validate->fails()) {
            $effect = Effects::create([
                'name' => $request->input('name')
            ]);
            return response()->json(['check' => true, 'data' => $effect]);
        } else {
            return response()->json(['check' => false, 'msg' => $validate->errors()->first()]);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $queryResult = Effects::where('id', $id)->first();
        $validate = Validator::make($data, [
            'name' => 'required|unique:effects,name'
        ]);
        if (!$validate->fails()) {
            if ($queryResult) {
                if ($request->has('name')) {
                    $data['updated_at'] = now();
                    Effects::where('id', $id)->update($data);
                    $effects = Effects::all();
                    return response()->json(['check' => true, 'data' => $effects]);
                } else {
                    return response()->json(['check' => false, 'msg' => 'Name field is required']);
                }
            } else {
                return response()->json(['check' => false, 'msg' => 'Effect is not found']);
            }
        } else {
            return response()->json(['check' => false, 'msg' => $validate->errors()->first()]);
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
}
