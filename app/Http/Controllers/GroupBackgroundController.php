<?php

namespace App\Http\Controllers;

use App\Models\Background;
use App\Models\GroupBackground;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Carbon\Carbon;

use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class GroupBackgroundController extends Controller
{
    protected $client;
    protected $aws_secret_key;
    protected $aws_access_key;
    public function __construct(Request $request)
    {
        $this->aws_secret_key = 'b52dcdbea046cc2cc13a5b767a1c71ea8acbe96422b3e45525d3678ce2b5ed3e';
        $this->aws_access_key = 'cbb3e2fea7c7f3e7af09b67eeec7d62c';
        $this->client = new Client();
    }

    // =====================================================
    private function uploadToCloudFlareFromFile1($imageFile, $folder, $filename)
    {
        try {
            // Step 1: Check if the file exists
            if (!file_exists($imageFile)) {
                Log::error('File does not exist: ' . $imageFile);
                return 'error: file does not exist';
            }
            $filename=str_replace(' ', '', $filename);
            // Step 2: Prepare Cloudflare R2 credentials and settings
            $accountid = '453d5dc9390394015b582d09c1e82365';
            $r2bucket = 'artapp';  // Updated bucket name
            $accessKey = $this->aws_access_key;
            $secretKey = $this->aws_secret_key;
            $region = 'auto';
            $endpoint = "https://$accountid.r2.cloudflarestorage.com";
            // Set up the S3 client with Cloudflare's endpoint
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => $region,
                'credentials' => [
                    'key' => $accessKey,
                    'secret' => $secretKey,
                ],
                'endpoint' => $endpoint,
                'use_path_style_endpoint' => true,
            ]);
            if ($imageFile instanceof UploadedFile) {
                $fileMimeType = $imageFile->getMimeType();
            } else {
                // For regular file paths, use mime_content_type()
                $fileMimeType = mime_content_type($imageFile);
            }

            $r2object = $folder . '/' . $filename;
            try {
                $result = $s3Client->putObject([
                    'Bucket' => $r2bucket,
                    'Key' => $r2object,
                    'Body' => fopen($imageFile, 'rb'),
                    'ContentType' => $fileMimeType,
                ]);
                $cdnUrl = "https://artapp.promptme.info/$folder/$filename";
                return $cdnUrl;
            } catch (S3Exception $e) {
                Log::error("Error uploading file: " . $e->getMessage());
                return 'error: ' . $e->getMessage();
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return 'error: ' . $th->getMessage();
        }
    }
    /**
     * Save the group backgrounds.
     */
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:group_backgrounds,name',
            'feature_id'=>'required|exists:features,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $data=$request->all();
        $data['slug']=Str::slug($request->name);
        $data['created_at']= now();
        GroupBackground::create($data);
        $data = GroupBackground::where('feature_id',$request->feature_id)->get();
        return response()->json(['check'=>true,'data'=>$data]);
    }

    public function uploadBackgroundImages(Request $request){
        $validator = Validator::make($request->all(), [
            'groupId'=>'required|exists:group_backgrounds,id',
            'images.*'=>'image'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        try {
            foreach ($request->file('images') as $image) {
                $filename = uniqid() . '_' . $image->getClientOriginalName();
                $folder = 'backgrounds';

                // Upload to Cloudflare
                $path = $this->uploadToCloudFlareFromFile1($image, $folder, $filename);

                // Store in database
                Background::create([
                    'path' => $path,
                    'group_id' => $request->groupId,
                    'status' => 1,
                    'is_front' => 0
                ]);
            }
            $data=Background::where('group_id',$request->groupId)->get();
            return response()->json([
                'check' => true,
                'msg' => 'Background images uploaded successfully',
                'data'=>$data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'check' => false,
                'msg' => 'Error uploading images: ' . $e->getMessage()
            ]);
        }
    }
// ==================================================
public function uploadFrontGroundImages(Request $request){
    $validator = Validator::make($request->all(), [
        'groupId'=>'required|exists:group_backgrounds,id',
        'images.*'=>'image'
    ]);
    if ($validator->fails()) {
        return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
    }
    try {
        foreach ($request->file('images') as $image) {
            $filename = uniqid() . '_' . $image->getClientOriginalName();
            $folder = 'backgrounds';

            // Upload to Cloudflare
            $path = $this->uploadToCloudFlareFromFile1($image, $folder, $filename);

            // Store in database
            Background::create([
                'url_front' => $path,
                'group_id' => $request->groupId,
                'status' => 1,
                'is_front' => 0
            ]);
        }
        $data=Background::where('group_id',$request->groupId)->get();
        return response()->json([
            'check' => true,
            'msg' => 'Background images uploaded successfully',
            'data'=>$data
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'check' => false,
            'msg' => 'Error uploading images: ' . $e->getMessage()
        ]);
    }
}
// ==================================================
    public function showBackground($id){
        $data = Background::where('group_id', $id)
    ->where(function ($query) {
        $query->where('path', '!=', null)
              ->orWhere('url_back', '!=', null);
    })
    ->get();
        return response()->json($data);
    }
    // ==================================================
    public function showFrontground($id){
        $data = Background::where('group_id', $id)
    ->where(function ($query) {
        $query->where('url_front', '!=', null);
    })
    ->get();
        return response()->json($data);
    }
    // ============================================
    public function show($id){
        $data = GroupBackground::where('feature_id',$id)->get();
        return response()->json($data);
    }
    // ============================================

    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|unique:group_backgrounds,name',
            'feature_id'=>'nullable|exists:features,id'
        ]);
        $data=$request->all();
        if($request->has('name')){
            $data['slug']=Str::slug($request->name);
        }
        $data['updated_at']= now();
        GroupBackground::where('id',$id)->update($data);
        $item= GroupBackground::where('id',$id)->first();
        $data = GroupBackground::where('feature_id',$item->feature_id)->get();
        return response()->json(['check'=>true,'data'=>$data]);
    }
    // ======================================================
    public function destroy($id){
        $item =GroupBackground::where('id',$id)->first();
        $feature_id=$item->feature_id;
        if(!$item){
            return response()->json(['check'=>false,'msg'=>'Không tìm thấy group']);
        }
        $item->delete();
        $result=GroupBackground::where('feature_id',$feature_id)->get();
        return response()->json(['check'=>true,'data'=>$result]);
    }

    public function saveGroupBackgrounds(Request $request)
    {
        try {
            // Validate incoming request
            $request->validate([
                'group_backgrounds' => 'required|array|min:1',
                'group_backgrounds.*' => 'string|max:255',
                'feature_id' => 'required|exists:features,id',
            ]);

            $createdGroups = [];

            // Loop through each group and save it
            foreach ($request->group_backgrounds as $groupName) {
                // Check if the group already exists
                $existingGroup = GroupBackground::where('name', $groupName)
                    ->where('feature_id', $request->feature_id)
                    ->first();

                // If the group does not exist, create it
                if (!$existingGroup) {
                    $group = GroupBackground::create([
                        'name' => $groupName,
                        'slug' => Str::slug($groupName),
                        'feature_id' => $request->feature_id,
                        'status' => 1,
                    ]);

                    $createdGroups[] = $group; // Add the created group to the response
                } else {
                    $createdGroups[] = $existingGroup; // Add the existing group to the response
                }
            }

            // Return success message with the created or existing groups
            return response()->json([
                'message' => 'Groups added successfully',
                'status' => true,
                'groups' => $createdGroups, // Return the created or existing groups
            ]);
        } catch (ValidationException $e) {
            // Handle validation error and provide details
            return response()->json([
                'message' => 'Validation failed',
                'status' => false,
                'errors' => $e->errors(), // Return detailed validation errors
            ], 422);
        } catch (\Exception $e) {
            // Handle any other errors
            return response()->json([
                'message' => 'Error saving groups',
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getBackgroundImagesByFeatureAndGroup($feature_id, Request $request)
    {
        $group = $request->query('group');
        $query = Background::where('feature_id', $feature_id);

        if ($group) {
            $query->where('group', $group); // filter by group if specified
        }

        $images = $query->get();
        return response()->json($images);
    }

    /**
     * Get backgrounds by feature ID, filtered by selected groups.
     */
    public function getBackgrounds($feature_id)
    {
        // Initialize query to get backgrounds for the given feature ID
        $query = Background::where('feature_id', $feature_id);

        // Check if there are specific group filters and apply them
        if (request()->has('group') && !empty(request()->group)) {
            $groupNames = explode(",", request()->group);

            // Retrieve group IDs based on provided names
            $groupIds = GroupBackground::whereIn('name', $groupNames)
                ->where('feature_id', $feature_id)
                ->pluck('id')
                ->toArray();

            // Filter backgrounds by the selected group IDs
            $query->whereIn('group_background_id', $groupIds);
        }

        // Fetch the filtered backgrounds
        $backgrounds = $query->get();

        return response()->json($backgrounds);
    }
    public function removeGroup(Request $request)
    {
        try {
            // Validate the incoming request to ensure 'group_name' and 'feature_id' are provided
            $request->validate([
                'group_name' => 'required|string|max:255',
                'feature_id' => 'required|exists:features,id',
            ]);

            // Find the group by name and feature_id
            $group = GroupBackground::where('name', $request->group_name)
                ->where('feature_id', $request->feature_id)
                ->first();

            if ($group) {
                // Delete the group from the database
                $group->delete();

                return response()->json([
                    'message' => 'Group removed successfully',
                    'status' => true,
                ]);
            } else {
                return response()->json([
                    'message' => 'Group not found',
                    'status' => false,
                ], 404);
            }
        } catch (\Exception $e) {
            // Handle any errors that occur during the process
            return response()->json([
                'message' => 'Error removing group',
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get groups by feature_id.
     */
    public function getGroupsByFeature($feature_id)
    {
        $groups = GroupBackground::where('feature_id', $feature_id)
            ->where('status', 1)
            ->get();

        return response()->json($groups);
    }
}
