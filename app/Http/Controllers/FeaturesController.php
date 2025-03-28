<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeatureRequest;
use App\Models\Features;
use App\Models\FeaturesSizes;
use App\Models\ImageSize;
use App\Models\Languages;
use App\Models\SubFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class FeaturesController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $features = Features::with('sizes')->get();
        $sizes = ImageSize::all();
        return Inertia::render('Features/Index', ['datafeatures' => $features, 'datasizes' => $sizes]);
    }


    //=============================================================
    public function update_feature_slug()
    {
        $result = Features::all();
        foreach ($result as $key => $item) {
            $item->update(['slug' => Str::slug($item->name)]);
        }
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

        $path = null;
        if ($request->hasFile('image')) {

            $path = $request->file('image')->store('features', 'public');
        }


        $feature = Features::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'slug' => Str::slug($request->input('name')),
            'api_endpoint' => $request->input('api_endpoint'),
            'image' => $path,
            'model_id' => $request->input('model_id'),
            'prompt' => $request->input('prompt'),
            'presetStyle' => $request->input('presetStyle'),
            'initImageId' => $request->input('initImageId'),
            'preprocessorId' => $request->input('preprocessorId'),
            'strengthType' => $request->input('strengthType')
        ]);
        $features = Features::with('sizes')->get();
        return response()->json(['check' => true, 'data' => $features]);
    }
    /**
     * Display the specified resource.
     */
    public function show(Features $features)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function api_index(Features $features, Request $request)
{
    $validator = Validator::make($request->all(), [
        'lang' => 'required|exists:languages_list,key',
    ]);
    if ($validator->fails()) {
        return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
    }
    $language = $request->lang;
    $features = Features::with(['subFeatures', 'sizes'])->active()->get();
    if ($features) {
        $features->makeHidden(['model_id', 'prompt', 'presetStyle', 'preprocessorId', 'strengthType', 'initImageId']);

        $features->each(function ($feature) use ($language) {
            $feature->subFeatures->each->makeHidden(['model_id', 'prompt', 'presetStyle', 'preprocessorId', 'strengthType', 'initImageId']);

            // Get translated name and description for the feature
            $languageData = Languages::where('api_slug', $feature->slug)->where('attribute','name')->first();
            $languageData1 = Languages::where('api_slug', $feature->slug)->where('attribute','description')->first();
            if ($languageData) {
                $feature->name = $languageData->$language ?? '';
                $feature->description = $languageData1->$language ??'';
                $feature->lang=$language;
            }
            $feature->subFeatures->each(function ($subFeature) use ($language) {
                $languageData = Languages::where('key', $subFeature->slug)->first();
                if ($languageData) {
                    $subFeature->name = $languageData->$language ?? $subFeature->name;
                    $subFeature->description = $languageData->$language ?? $subFeature->description;
                    $subFeature->lang=$language;

                }
            });
        });
    }
    $highlightedSubFeatures = SubFeatures::where('is_highlight', 1)
        ->get()
        ->map(function ($subFeature) use ($language) {
            $subFeature->makeHidden(['model_id', 'prompt', 'presetStyle', 'preprocessorId', 'strengthType', 'initImageId', 'feature_id', 'is_highlight']);

            $languageData = Languages::where('api_slug', $subFeature->slug)->first();

            if ($languageData) {
                $subFeature->name = $languageData->$language ?? $subFeature->name;
                $subFeature->description = $languageData->$language ?? $subFeature->description;
                $subFeature->lang=$language;
            }

            $featureId = $subFeature->feature_id;
            $subFeaturesOfFeature = SubFeatures::where('feature_id', $featureId)
                ->get()
                ->map(function ($siblingSubFeature) use ($language) {
                    $siblingSubFeature->makeHidden(['model_id', 'prompt', 'presetStyle', 'preprocessorId', 'strengthType', 'initImageId']);

                    $languageData = Languages::where('api_slug', $siblingSubFeature->slug)->first();

                    if ($languageData) {
                        $siblingSubFeature->name = $languageData->$language ?? $siblingSubFeature->name;
                        $siblingSubFeature->description = $languageData->$language ?? $siblingSubFeature->description;
                        $siblingSubFeature->lang=$language;
                    }

                    return $siblingSubFeature;
                });
            $subFeature->setAttribute('is_effect', 0);
            $subFeature->setAttribute('sub_features', $subFeaturesOfFeature);
            $subFeature->setAttribute('sizes', []);
            return $subFeature;
        });
    $features = $features->merge($highlightedSubFeatures);

    return response()->json($features);
}

    // public function api_index(Features $features,Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'lang' => 'required|in:en,vi,de,ksl,pl,nu',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
    //     }
    //     $features = Features::with(['subFeatures', 'sizes'])->active()->get();
    //     if ($features) {
    //         // Hide the attributes in the features model
    //         $features->makeHidden(['model_id', 'prompt', 'presetStyle', 'preprocessorId', 'strengthType', 'initImageId']);

    //         // Loop through each subFeature and hide the specified attributes
    //         $features->each(function ($feature) {
    //             $feature->subFeatures->each->makeHidden(['model_id', 'prompt', 'presetStyle', 'preprocessorId', 'strengthType', 'initImageId']);
    //             $language=$request->lang;
    //             $name = Languages::where('key',$feature->slug)->value();
    //         });
    //     }
    //     $highlightedSubFeatures = SubFeatures::where('is_highlight', 1)
    //         ->get()
    //         ->map(function ($subFeature) {
    //             $subFeature->makeHidden(['model_id', 'prompt', 'presetStyle', 'preprocessorId', 'strengthType', 'initImageId', 'feature_id', 'is_highlight']);
    //             $featureId = $subFeature->feature_id;
    //             $subFeaturesOfFeature = SubFeatures::where('feature_id', $featureId)
    //                 ->get()
    //                 ->map(function ($siblingSubFeature) {
    //                     $siblingSubFeature->makeHidden(['model_id', 'prompt', 'presetStyle', 'preprocessorId', 'strengthType', 'initImageId']);
    //                     return $siblingSubFeature;
    //                 });
    //             $subFeature->setAttribute('is_effect', 0);
    //             $subFeature->setAttribute('sub_features', $subFeaturesOfFeature);
    //             $subFeature->setAttribute('sizes', []);

    //             return $subFeature;
    //         });
    //     $features = $features->merge($highlightedSubFeatures);
    //     return response()->json($features);
    // }
    public function api_detail(Features $features, $id,Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lang' => 'required|exists:languages_list,key',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $features = Features::with(['subFeatures', 'sizes'])->where('id', $id)->active()->first();
        $language = $request->lang;
        if ($features) {
            $features->makeHidden(['model_id', 'prompt', 'presetStyle', 'preprocessorId', 'strengthType', 'initImageId']);
             $languageData = Languages::where('api_slug', $features->slug)->where('attribute','name')->first();
             $languageData1 = Languages::where('api_slug', $features->slug)->where('attribute','description')->first();
             if ($languageData) {
                 $features->name = $languageData->$language ?? '';
                 $features->description = $languageData1->$language ??'';
                 $features->lang=$language;
             }
             $features->subFeatures->each(function ($subFeature) use ($language) {
                 $languageData = Languages::where('key', $subFeature->slug)->first();
                 if ($languageData) {
                     $subFeature->name = $languageData->$language ?? $subFeature->name;
                     $subFeature->description = $languageData->$language ?? $subFeature->description;
                     $subFeature->lang=$language;

                 }
             });
        }

        return response()->json($features);
    }
    /**
     * Update the specified resource in storage.
     */

    public function updated_size(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'size_id' => 'nullable|array',
            'size_id.*' => 'exists:image_sizes,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $arr = $request->size_id;
        FeaturesSizes::where('feature_id', $id)->delete();
        if ($arr != null) {
            foreach ($arr as $key => $value) {
                FeaturesSizes::create([
                    'feature_id' => $id,
                    'size_id' => $value,
                    'created_at' => now()
                ]);
            }
        }
        $data = Features::with('sizes')->get();
        return response()->json(['check' => true, 'data' => $data]);
    }
    public function update(FeatureRequest $request, $id)
    {
        $data = $request->all();
        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->input('name'));
        }
        $data['updated_at'] = now();
        Features::where('id', $id)->update($data);
        $features = Features::with('sizes')->get();
        return response()->json(['check' => true, 'data' => $features]);
    }

    public function feature_update_image($id)
    {
        $result = Features::where('id', $id)->first();
        if (!$result) {
            return response()->json(['check' => false, 'msg' => 'Không tìm thấy feature']);
        }
        if (!request()->hasFile('image')) {
            return response()->json(['check' => false, 'msg' => 'Vui lòng chọn hình ảnh']);
        }
        $filePath = storage_path('public/features' . $result->image);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $image = request()->file('image');
        $path = $image->storeAs('public/features', $image->getClientOriginalName());
        $data['image'] = 'features/' . $image->getClientOriginalName();
        $data['updated_at'] = now();
        Features::where('id', $id)->update($data);
        $data =  Features::with('sizes')->get();
        return response()->json(['check' => true, 'data' => $data]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeatureRequest $features, $id)
    {
        Features::where('id', $id)->delete();
        $data =  Features::with('sizes')->get();
        return response()->json(['check' => true, 'data' => $data]);
    }
}
