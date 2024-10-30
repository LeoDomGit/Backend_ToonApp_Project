<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImageAIController extends Controller
{
    protected $key ='eyJraWQiOiI5NzIxYmUzNi1iMjcwLTQ5ZDUtOTc1Ni05ZDU5N2M4NmIwNTEiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJhdXRoLXNlcnZpY2UtYzJlZWMwZTQtZTRiOS00MTQwLWFmZGEtNDU4N2FmNDAwMGYxIiwiYXVkIjoiNDY2OTAyOTQxMDEwMTAxIiwibmJmIjoxNzI5MjA2OTY5LCJzY29wZSI6WyJiMmItYXBpLmdlbl9haSIsImIyYi1hcGkuaW1hZ2VfYXBpIl0sImlzcyI6Imh0dHBzOi8vYXBpLnBpY3NhcnQuY29tL3Rva2VuLXNlcnZpY2UiLCJvd25lcklkIjoiNDY2OTAyOTQxMDEwMTAxIiwiaWF0IjoxNzI5MjA2OTY5LCJqdGkiOiI0ZWNjZjdjOC0yOGQ2LTRlMTMtYTM1Ni02NTg0NzYzY2E1OGUifQ.WZpBVZzQhO9efRWXw4ZP4FoUjPoNf1qhsK2vBIBobKhx-mZZwYJwwq2EKBsbpE0LjR5bbC-BknHhVAxu-ww1G1uekARRN2H6pt6jlUcLpxIcG1RJBBeeSBeGH3b2T1Q16nABvhR16QOhBid0lMcOcaANkTGrmpYOMg4el2h4wBEVzDn0NwhnUVgosUipVq37wOl7iZ56flZUCektLm8BFor47W7Pq0HqbuHEx8s2LU56NIBF119AK4Tuw3ERiDadELOEjOGP8VZ0gvafWTbQpEmqEz84x1TWq7hH0nwroq5a2x3QblmgCmdZx7AZH25TNqd5RrDe5fDlJtx5350H2g';
    /**
     * Display a listing of the resource.
     */
    public function ai_cartoon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required',
        ], [
            'role.required' => 'Chưa có loại tài khoản',
            'role.exists' => 'Mã loại tài khoản không hợp lệ',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
    }
    public function changeBackground(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function cartoonStyle(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function slideCompare(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function removeBackground(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function claymation(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function disneyToon(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function disneyCharators(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function fullBodyCartoon(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function animalToon(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function newProfilePic(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function funnyCharactors(Request $request)
    {
        return response()->json(['status' => 'develop']);
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
        //
    }

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
