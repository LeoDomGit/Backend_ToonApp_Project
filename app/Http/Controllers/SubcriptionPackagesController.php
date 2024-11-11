<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackageRequest;
use App\Models\SubcriptionPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class SubcriptionPackagesController extends Controller
{
    public function index()
    {
        $subcriptionPackages = SubcriptionPackage::paginate(10);

        return Inertia::render('Packages/Index', [
            'data' => $subcriptionPackages,
        ]);
    }

    public function create()
    {
        return Inertia::render('Packages/Create');
    }

    public function store(PackageRequest $request)
    {
        try {
            // Validate incoming request data
            $data = $request->validated();

            // Create new subscription package
            $subcriptionPackage = SubcriptionPackage::create($data);

            return response()->json([
                'check' => true,
                'data' => $subcriptionPackage,
                'msg' => 'Package created successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating package: ' . $e->getMessage());
            return response()->json([
                'check' => false,
                'msg' => 'An error occurred while creating the package.',
            ]);
        }
    }


    public function show(SubcriptionPackage $subcriptionPackage)
    {
        return Inertia::render('Packages/Show', [
            'data' => $subcriptionPackage,
        ]);
    }

    public function edit(SubcriptionPackage $subcriptionPackage)
    {
        return Inertia::render('Packages/Edit', [
            'data' => $subcriptionPackage,
        ]);
    }

    public function update(PackageRequest $request, SubcriptionPackage $subcriptionPackage)
    {
        try {
            $data = $request->validated();

            // Ensure required fields are present
            if (!isset($data['duration']) || !isset($data['status']) || !isset($data['payment_method'])) {
                return response()->json([
                    'check' => false,
                    'msg' => 'Missing required fields (duration, status, payment_method).',
                ], 422);
            }

            $subcriptionPackage->update($data);

            return response()->json([
                'check' => true,
                'data' => $subcriptionPackage,
                'msg' => 'Package updated successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating package: ' . $e->getMessage());
            return response()->json([
                'check' => false,
                'msg' => 'Error updating package: ' . $e->getMessage(),
            ]);
        }
    }
    public function destroy(SubcriptionPackage $subcriptionPackage)
    {
        try {
            $subcriptionPackage->delete();

            return response()->json([
                'check' => true,
                'msg' => 'Package deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting package: ' . $e->getMessage());
            return response()->json([
                'check' => false,
                'msg' => 'Error deleting package: ' . $e->getMessage(),
            ]);
        }
    }
}
