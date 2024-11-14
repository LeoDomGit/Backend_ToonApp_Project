<?php

namespace App\Http\Controllers;

use App\Models\Activities;
use App\Models\Background;
use App\Models\FeatureImage;
use App\Models\Features;
use App\Models\SubFeatures;
use App\Models\FeaturesSizes;
use App\Models\ImageSize;
use App\Models\Key;
use App\Models\Photos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Storage;
use App\Models\Customers;

class ImageAIController extends Controller
{
    protected $key;
    protected $leo_key;
    protected $client;
    protected $aws_secret_key;
    protected $aws_access_key;
    protected $pro_account;

    /**
     * Display a listing of the resource.
     */

    public function __construct(Request $request)
    {
        $result = Key::where('api', 'picsart')->orderBy('id', 'asc')->first();
        $this->key = $result->key;

        $this->leo_key = env('IMAGE_API_KEY');
        $this->aws_secret_key = 'b52dcdbea046cc2cc13a5b767a1c71ea8acbe96422b3e45525d3678ce2b5ed3e';
        $this->aws_access_key = 'cbb3e2fea7c7f3e7af09b67eeec7d62c';
        $this->client = new Client();
        $guard = Auth::guard('customer');
        $user = $guard->user();
        $bearerToken = $request->bearerToken();
        if ($bearerToken && $bearerToken === config('app.access_token')) {
            $this->pro_account = true;
        }
        // elseif ($user && $user->expired_at && !Carbon::parse($user->expired_at)->isPast()) {
        //     $this->pro_account = true;
        // }
        else {
            $this->pro_account = false;
        }
    }
    private function uploadServerImage($image)
    {
        $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $folder = 'users-' . Auth::guard('customer')->id() . '/';
        $code_profile = 'image-' . time();

        $cdn = $this->uploadToCloudFlareFromFile($image, $folder, $filename);
        $id = Photos::insertGetId([
            'customer_id' => Auth::guard('customer')->id(),
            'original_image_path' => $cdn,
        ]);
        return $id;
    }
    /**
     * Store a newly created resource in storage.
     */
    public function getEffect(Request $request){
        $response = Http::withHeaders([
            'X-Picsart-API-Key' => $this->key,
            'Accept' => 'application/json',
        ])->get('https://api.picsart.io/tools/1.0/effects');

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['data'])) {
                // Return only the effect names
                $effectNames = array_column($data['data'], 'name');
                return response()->json($effectNames);
            } else {
                return response()->json(['error' => 'No data found in response.'], 404);
            }
        } else {
            return response()->json([
                'error' => 'Failed to retrieve effects from Picsart API.',
                'details' => $response->json(),
                'status' => $response->status()
            ], $response->status());
        }
    }
        /**
     * Store a newly created resource in storage.
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
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }
    }
    public function changeBackground(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }
        if($result->is_pro==1 && $this->pro_account==false){
            return response()->json(['status'=>false,'error'=>'Not accepted'],401);
        }
        $image=$request->file('image');
        $imageContent = file_get_contents($image);
        $tempFilePath = storage_path('app/public/anime/temp_image.jpg');
        file_put_contents($tempFilePath, $imageContent);
        $routePath = $request->path();
        $feature=Features::where('api_endpoint', $routePath)->first();
        if($request->has('background')){
            $response = Http::withHeaders([
                'X-Picsart-API-Key' => $this->key,
                'Accept' => 'application/json',
            ])->attach(
                'image',
                file_get_contents($tempFilePath),
                'temp_image.jpg'
            )->post('https://api.picsart.io/tools/1.0/removebg', [
                'output_type' => 'cutout',
                'bg_blur' => '0',
                'scale' => 'fit',
                'auto_center' => 'false',
                'stroke_size' => '0',
                'stroke_color' => 'FFFFFF',
                'stroke_opacity' => '100',
                'shadow' => 'disabled',
                'shadow_opacity' => '20',
                'shadow_blur' => '50',
                'format' => 'PNG',
                'bg_image_url'=>$request->background
            ]);

            // Check response status
            if ($response->successful()) {
                $data = $response->json();
                $processedImageUrl = $data['data']['url'];

                activity('remove_background')
                    ->withProperties([
                        'cdnUrl' => $processedImageUrl,
                        'sourceUrl' => $image,
                    ])
                    ->log('Image processed successfully');
                    $image = $this->uploadToCloudFlareFromCdn(
                        $processedImageUrl,
                        'image-' . time(),
                        $feature->slug,
                        Auth::guard('customer')->id() . 'result-gen'.time()
                    );
                    return response()->json(['url'=>$image]);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Failed to process image', 'error' => $response->json()], 400);
            }
        }else{
            $response = Http::withHeaders([
                'X-Picsart-API-Key' => $this->key,
                'Accept' => 'application/json',
            ])->attach(
                'image',
                file_get_contents($tempFilePath),
                'temp_image.jpg'
            )->post('https://api.picsart.io/tools/1.0/removebg', [
                'output_type' => 'cutout',
                'bg_blur' => '0',
                'scale' => 'fit',
                'auto_center' => 'false',
                'stroke_size' => '0',
                'stroke_color' => 'FFFFFF',
                'stroke_opacity' => '100',
                'shadow' => 'disabled',
                'shadow_opacity' => '20',
                'shadow_blur' => '50',
                'format' => 'PNG',
            ]);

            // Check response status
            if ($response->successful()) {
                $data = $response->json();
                $processedImageUrl = $data['data']['url'];

                activity('remove_background')
                    ->withProperties([
                        'cdnUrl' => $processedImageUrl,
                        'sourceUrl' => $image,
                    ])
                    ->log('Image processed successfully');
                    $image = $this->uploadToCloudFlareFromCdn(
                        $processedImageUrl,
                        'image-' . time(),
                        $feature->slug,
                        Auth::guard('customer')->id() . 'result-gen'.time()
                    );
                    return response()->json(['url'=>$image]);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Failed to process image', 'error' => $response->json()], 400);
            }
        }

    }


    public function cartoonStyle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
            'level' => 'in:l1,l2,l3,l4,l5',
        ]);

        if ($validator->fails()) {
            activity('claymation')
                ->withProperties(['error' => $validator->errors()->first()])
                ->log('Validation failed');

            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }
        $routePath = $request->path();
        $result = FeatureImage::where('api_route', $routePath)->first()->value('path');
        $image_url = config('app.image_url') . $result;
        $image = $request->file('image');
        $id_img = $this->uploadServerImage($image);
        $level = $request->input('level', 'l5');
        activity('removeBackground')
            ->withProperties(['id_img' => $id_img])
            ->log('Removing background from image');

        // Send request to Picsart API to remove the background
        $response = Http::withHeaders([
            'X-Picsart-API-Key' => $this->key,
            'Accept' => 'application/json',
        ])->attach(
            'image',
            file_get_contents($image->getRealPath()),
            $image->getClientOriginalName()
        )->post('https://api.picsart.io/tools/1.0/removebg', [
            'output_type' => 'cutout',
            'bg_blur' => '0',
            'scale' => 'fit',
            'auto_center' => 'false',
            'stroke_size' => '0',
            'stroke_color' => 'FFFFFF',
            'stroke_opacity' => '100',
            'shadow' => 'disabled',
            'shadow_opacity' => '20',
            'shadow_blur' => '50',
            'format' => 'PNG',
        ]);

        // Check response status for background removal
        if ($response->successful()) {
            $data = $response->json();
            $backgroundRemovedUrl = $data['data']['url'];
            $response = Http::withHeaders([
                'X-Picsart-API-Key' => $this->key,
                'Accept' => 'application/json',
            ])->attach(
                'image',
                file_get_contents($backgroundRemovedUrl), // Use the URL directly from the remove background response
                basename($backgroundRemovedUrl) // Use the name of the original image or appropriate name
            )->post('https://api.picsart.io/tools/1.0/styletransfer', [
                [
                    'name' => 'level',
                    'contents' => $level
                ],
                [
                    'name' => 'format',
                    'contents' => 'JPG'
                ],
                [
                    'name' => 'reference_image_url',
                    'contents' => $image_url
                ]
            ]);

            // Check response status for style transfer
            if ($response->successful()) {
                $data = $response->json();
                $image_url = $data['data']['url'];
                $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $folder = 'Styletransfer';
                $code_profile = 'image-' . time();
                $cdnUrl = $this->uploadToCloudFlareFromFile($image_url, $code_profile, $folder, $filename);
                activity('claymation')
                    ->withProperties([
                        'cdnUrl' => $cdnUrl,
                        'size' => $image->getSize(),
                    ])
                    ->log('Image processed successfully');
                $this->createActivities($id_img, $cdnUrl, $image->getSize(), '/api/claymation', 'https://api.picsart.io/tools/1.0/styletransfer');
                return response()->json(['status' => true, 'url' => $cdnUrl]);
            } else {
                activity('claymation')
                    ->withProperties(['error' => $response->body()])
                    ->log('Failed to process image');

                return response()->json(['status' => 'error', 'message' => 'Failed to process image', 'error' => $response->body()], 400);
            }
        } else {
            activity('removeBackground')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to remove background');

            return response()->json(['status' => 'error', 'message' => 'Failed to remove background', 'error' => $response->body()], 400);
        }
    }
    private function storeRequest($request_type, $prompt, $modelai, $method, $url_endpoint, $postfields, $response, $id_content_category)
    {
        $request = new RequestModel();
        $request->id_user = Auth::guard('customer')->id();
        $request->request_type = $request_type;
        $request->prompt = $prompt;
        $request->code_model_ai = $modelai;
        $request->method = $method;
        $request->endpoint = $url_endpoint;
        $request->postfields = $postfields;
        $request->response = $response;
        $request->id_content_category = $id_content_category;
        $request->save();

        return $request->id_request;
    }
    private function uploadToCloudFlareFromFile($file, $folder, $filename)
    {
        try {
            // Step 1: Prepare Cloudflare R2 credentials and settings
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

            // Step 2: Define the object path and name in R2
            $r2object = $folder . '/' . $filename . '.' . $file->getClientOriginalExtension();

            // Step 3: Upload the file to Cloudflare R2
            try {
                $result = $s3Client->putObject([
                    'Bucket' => $r2bucket,
                    'Key' => $r2object,
                    'Body' => file_get_contents($file->getRealPath()), // Get the file content
                    'ContentType' => $file->getMimeType(),
                ]);

                // Generate the CDN URL using the custom domain
                $cdnUrl = "https://artapp.promptme.info/$folder/$filename." . $file->getClientOriginalExtension();
                return $cdnUrl;
            } catch (S3Exception $e) {
                Log::error("Error uploading file: " . $e->getMessage());
                return 'error' . $e->getMessage();
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return 'error';
        }
    }
    private function uploadToCloudFlareFromCdn($image_url, $code_profile, $folder, $filename)
    {
        try {
            // Step 1: Prepare Cloudflare R2 credentials and settings
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

            // Step 2: Stream image directly from CDN
            $imageData = file_get_contents($image_url);

            if ($imageData === false) {
                // Handle download error
                Log::error('Failed to retrieve image from CDN URL: ' . $image_url);
                return 'error';
            }

            // Step 3: Define the object path and name in R2
            $r2object = $folder . '/' . $filename . '.jpg';

            // Step 4: Upload the file to Cloudflare R2
            try {
                $result = $s3Client->putObject([
                    'Bucket' => $r2bucket,
                    'Key' => $r2object,
                    'Body' => $imageData,  // Pass the image content directly from CDN
                    'ContentType' => 'image/jpeg',
                ]);

                // Generate the CDN URL using the custom domain
                $cdnUrl = "https://artapp.promptme.info/$folder/$filename.jpg";
                return $cdnUrl;
            } catch (S3Exception $e) {
                Log::error("Error uploading file: " . $e->getMessage());
                return 'error: ' . $e->getMessage();
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return 'error';
        }
    }
    public function removeBackground($image)
    {
        $imageContent = file_get_contents($image);
        $tempFilePath = storage_path('app/public/anime/temp_image.jpg');
        file_put_contents($tempFilePath, $imageContent);
        $response = Http::withHeaders([
            'X-Picsart-API-Key' => $this->key,
            'Accept' => 'application/json',
        ])->attach(
            'image',
            file_get_contents($tempFilePath),
            'temp_image.jpg'
        )->post('https://api.picsart.io/tools/1.0/removebg', [
            'output_type' => 'cutout',
            'bg_blur' => '0',
            'scale' => 'fit',
            'auto_center' => 'false',
            'stroke_size' => '0',
            'stroke_color' => 'FFFFFF',
            'stroke_opacity' => '100',
            'shadow' => 'disabled',
            'shadow_opacity' => '20',
            'shadow_blur' => '50',
            'format' => 'PNG',
        ]);

        // Check response status
        if ($response->successful()) {
            $data = $response->json();
            $processedImageUrl = $data['data']['url'];

            activity('remove_background')
                ->withProperties([
                    'cdnUrl' => $processedImageUrl,
                    'sourceUrl' => $image,
                ])
                ->log('Image processed successfully');
            return $processedImageUrl;
        } else {
            return response()->json(['status' => 'error', 'message' => 'Failed to process image', 'error' => $response->json()], 400);
        }
    }

    public function animalToon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $image = $request->file('image');
        $effectName = 'badlands';
        $id_img = $this->uploadServerImage($image);

        // Send request to Picsart API
        $response = Http::withHeaders([
            'X-Picsart-API-Key' => $this->key,
            'Accept' => 'application/json',
        ])->attach(
            'image',
            file_get_contents($image->getRealPath()),
            $image->getClientOriginalName()
        )->post('https://api.picsart.io/tools/1.0/effects/ai', [
            [
                'name' => 'effect_name',
                'contents' => $effectName
            ],
            [
                'name' => 'format',
                'contents' => 'JPG'
            ]
        ]);

        // Check response status
        if ($response->successful()) {
            $data = $response->json();
            $image_url = $data['data']['url'];
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $folder = 'AIEffects';
            $code_profile = 'image-' . time();
            $cdnUrl = $this->uploadToCloudFlareFromFile($image_url, $code_profile, $folder, $filename);
            activity('animalToon')
                ->withProperties([
                    'id_img' => $id_img,
                    'cdnUrl' => $cdnUrl,
                    'image_size' => $image->getSize(),
                    'api_url' => 'https://api.picsart.io/tools/1.0/effects/ai'
                ])
                ->log('Processed image for animal toon effect');
            $this->createActivities($id_img, $cdnUrl, $image->getSize(), '/api/animal_toon', 'https://api.picsart.io/tools/1.0/effects/ai');
            return response()->json(['status' => true, 'url' => $cdnUrl]);
        } else {
            // Log activity on failure
            activity('animalToon')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to process image for animal toon effect');

            return response()->json(['status' => 'error', 'message' => 'Failed to process image', 'error' => $response->body()], 400);
        }
    }
    private function uploadToCloudFlareFromFile1($imageFile, $code_profile, $folder, $filename)
    {
        try {
            // Step 1: Check if the file exists
            if (!file_exists($imageFile)) {
                Log::error('File does not exist: ' . $imageFile);
                return 'error: file does not exist';
            }

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

            // Step 3: Define the object path and name in R2
            $r2object = $folder . '/' . $filename . '.jpg';

            // Step 4: Upload the file to Cloudflare R2
            try {
                $result = $s3Client->putObject([
                    'Bucket' => $r2bucket,
                    'Key' => $r2object,
                    'Body' => fopen($imageFile, 'rb'), // Open the file as a binary stream
                    'ContentType' => 'image/jpeg',
                ]);

                // Generate the CDN URL using the custom domain
                $cdnUrl = "https://artapp.promptme.info/$folder/$filename.jpg";
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
    public function createActivities($photoId, $imageResult, $imageSize, $featuresId, $apiEndpoint, $aiModel = null)
    {
        // Set default AI model to 'Picsart' if not provided
        $aiModel = $aiModel ?? 'Picsart';
        $result = Features::where('api_endpoint', 'like', '%' . $featuresId . '%')->first();
        $featuresId = $result->id;
        return Activities::create([
            'customer_id' => Auth::guard('customer')->id(),
            'photo_id' => $photoId,
            'features_id' => $featuresId,
            'image_result' => $imageResult,
            // 'image_size' => $imageSize,
            'ai_model' => $aiModel,
            'api_endpoint' => $apiEndpoint,
        ]);
    }
    public function test()
    {
        return response()->json(config('app.image_url'));
    }
    public function claymation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
            'slug'=>'required',
            'id_size'=>'nullable|exists:image_sizes,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }
        $file = $request->file('image');
        $result = $this->uploadImage($file);
        $image_id = $result['id'];
        $result = Features::where('slug', $request->slug)->first();
        $feature=Features::where('slug', $request->slug)->first();
        if($result){
        $id_feature=$feature->id;
        }
        if(!$result){
            $result =SubFeatures::where('slug', $request->slug)->first();
            $feature=SubFeatures::where('slug', $request->slug)->first();
            $id_feature=$feature->feature_id;
        }
        if($result->is_pro==1 && $this->pro_account==false){
            return response()->json(['status'=>false,'error'=>'Not accepted'],401);
        }
        $initImageId = $result->initImageId;
        if($request->has('id_size')){
            $check=FeaturesSizes::where([
                'feature_id'=>$id_feature,
                'size_id'=>$request->id_size
            ])->first();
            if(!$check){
                return response()->json(['status'=>'error','message'=>'Size này không được hỗ trợ trong feature'],400);
            }
            $size=ImageSize::where('id',$request->id_size)->first();
            $height=$size->height;
            $width=$size->width;
            $initImageId=$result->initImageId;
            $featuresId = $result->id;
            $folder = 'cartoon';
            $filename =  pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $id_img = $this->uploadServerImage($file);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->leo_key,
                'Accept' => 'application/json',
            ])->post('https://cloud.leonardo.ai/api/rest/v1/generations', [
                'height' => $height,
                'modelId' => $result->model_id,
                'prompt' => $result->prompt,
                'presetStyle' => $result->presetStyle,
                'width' => $width,
                'num_images' => 1,
                'alchemy' => true,
                isset($initImageId) && $initImageId !== null ? [
                    'controlnets' => [
                        [
                            'initImageId' => $initImageId,
                            'initImageType' => 'UPLOADED',
                            'preprocessorId' => (int) $result->preprocessorId,
                            'strengthType' => 'High',
                        ]
                    ]
                ] : [],
                "init_image_id" => $image_id,
                "init_strength" => 0.5,
            ]);
            if ($response->successful()) {
                $data = $response->body();
                $data = json_decode($data, true);
                $generationId = $data['sdGenerationJob']['generationId'];
                while (true) {
                    $response = Http::withHeaders([
                        'accept' => 'application/json',
                        'authorization' =>'Bearer ' . $this->leo_key,
                    ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/'.$generationId);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (!empty($data['generations_by_pk']['generated_images'])) {
                            // Get the original image URL and upload it to Cloudflare
                            $firstImageUrl = $data['generations_by_pk']['generated_images'][0]['url'];
                            $originalImageUrl = $this->uploadToCloudFlareFromCdn(
                                 $data['generations_by_pk']['generated_images'][0]['url'],
                                'image-result' . time(),
                                $feature->slug,
                                Auth::guard('customer')->id() . '-gen' . $generationId
                            );
                            // By default, set $image to $originalImageUrl
                            $image = $originalImageUrl;
                            // Check if background removal is enabled
                            if ($feature->remove_bg == 1) {
                                $imageWithoutBg = $this->removeBackground($originalImageUrl);
                                $image = $this->uploadToCloudFlareFromCdn(
                                    $imageWithoutBg,
                                    'image-' . time(),
                                    $feature->slug,
                                    Auth::guard('customer')->id() . 'result-gen' . $generationId
                                );
                            }
                            // Log the activity with the final image URL
                            Activities::create([
                                'customer_id' => Auth::guard('customer')->id(),
                                'photo_id' => $id_img,
                                'features_id' => $featuresId,
                                'image_result' => $image,
                                'image_size' => $result->width,
                                'ai_model' => 'Leo AI',
                                'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                            ]);

                            // Return the JSON response with both the original and modified URLs
                            if($feature->remove_bg == 1){
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,              // Final image URL (with or without background removed)
                                    'bg_url' => $originalImageUrl  // Original image URL
                                ]);
                            }else{
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,
                                ]);
                            }
                        }
                    }
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
            }
        }else{
            $initImageId=$result->initImageId;
            $featuresId = $result->id;
            $folder = 'cartoon';
            $filename =  pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $id_img = $this->uploadServerImage($file);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->leo_key,
                'Accept' => 'application/json',
            ])->post('https://cloud.leonardo.ai/api/rest/v1/generations', [
                'modelId' => $result->model_id,
                'prompt' => $result->prompt,
                'presetStyle' => $result->presetStyle,
                'num_images' => 1,
                'alchemy' => true,
                isset($initImageId) && $initImageId !== null ? [
                    'controlnets' => [
                        [
                            'initImageId' => $initImageId,
                            'initImageType' => 'UPLOADED',
                            'preprocessorId' => (int) $result->preprocessorId,
                            'strengthType' => 'High',
                        ]
                    ]
                ] : [],
                "init_image_id" => $image_id,
                "init_strength" => 0.5,
            ]);
            if ($response->successful()) {
                $data = $response->body();
                $data = json_decode($data, true);
                $generationId = $data['sdGenerationJob']['generationId'];
                while (true) {
                    $response = Http::withHeaders([
                        'accept' => 'application/json',
                        'authorization' =>'Bearer ' . $this->leo_key,
                    ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/'.$generationId);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (!empty($data['generations_by_pk']['generated_images'])) {
                            // Get the original image URL and upload it to Cloudflare
                            $firstImageUrl = $data['generations_by_pk']['generated_images'][0]['url'];
                            $originalImageUrl = $this->uploadToCloudFlareFromCdn(
                                 $data['generations_by_pk']['generated_images'][0]['url'],
                                'image-result' . time(),
                                $feature->slug,
                                Auth::guard('customer')->id() . '-gen' . $generationId
                            );
                            // By default, set $image to $originalImageUrl
                            $image = $originalImageUrl;
                            // Check if background removal is enabled
                            if ($feature->remove_bg == 1) {
                                $imageWithoutBg = $this->removeBackground($originalImageUrl);
                                $image = $this->uploadToCloudFlareFromCdn(
                                    $imageWithoutBg,
                                    'image-' . time(),
                                    $feature->slug,
                                    Auth::guard('customer')->id() . 'result-gen' . $generationId
                                );
                            }
                            // Log the activity with the final image URL
                            Activities::create([
                                'customer_id' => Auth::guard('customer')->id(),
                                'photo_id' => $id_img,
                                'features_id' => $featuresId,
                                'image_result' => $image,
                                'image_size' => $result->width,
                                'ai_model' => 'Leo AI',
                                'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                            ]);

                            // Return the JSON response with both the original and modified URLs
                            if($feature->remove_bg == 1){
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,              // Final image URL (with or without background removed)
                                    'bg_url' => $originalImageUrl  // Original image URL
                                ]);
                            }else{
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,
                                ]);
                            }
                        }
                    }
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
            }
        }
    }

    public function disneyToon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
            'reference_image' => 'required|mimes:png,jpg,jpeg',
            'level' => 'in:l1,l2,l3,l4,l5',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $image = $request->file('image');
        $id_img = $this->uploadServerImage($image);
        $referenceImage = $request->file('reference_image');
        $level = $request->input('level', 'l2'); // Default to l2

        // Send request to Picsart API
        $response = Http::withHeaders([
            'X-Picsart-API-Key' => $this->key,
            'Accept' => 'application/json',
        ])->attach(
            'image',
            file_get_contents($image->getRealPath()),
            $image->getClientOriginalName()
        )->attach(
            'reference_image',
            file_get_contents($referenceImage->getRealPath()),
            $referenceImage->getClientOriginalName()
        )->post('https://api.picsart.io/tools/1.0/styletransfer', [
            [
                'name' => 'level',
                'contents' => $level
            ],
            [
                'name' => 'format',
                'contents' => 'JPG'
            ]
        ]);

        // Check response status
        if ($response->successful()) {
            $data = $response->json();
            $image_url = $data['data']['url'];
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $folder = 'Styletransfer';
            $code_profile = 'image-' . time();
            $cdnUrl = $this->uploadToCloudFlareFromFile($image_url, $code_profile, $folder, $filename);
            activity('disneyToon')
                ->withProperties([
                    'id_img' => $id_img,
                    'cdnUrl' => $cdnUrl,
                    'image_size' => $image->getSize(),
                    'api_url' => 'https://api.picsart.io/tools/1.0/styletransfer'
                ])
                ->log('Processed image for Disney-style transformation');
            $this->createActivities($id_img, $cdnUrl, $image->getSize(), '/api/claymation', 'https://api.picsart.io/tools/1.0/styletransfer');
            return response()->json(['status' => true, 'url' => $cdnUrl]);
        } else {
            // Log failed activity attempt
            activity('disneyToon')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to process image for Disney-style transformation');

            return response()->json(['status' => 'error', 'message' => 'Failed to process image', 'error' => $response->body()], 400);
        }
    }

    public function disneyCharators(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
            'reference_image' => 'required|mimes:png,jpg,jpeg',
            'level' => 'in:l1,l2,l3,l4,l5',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $image = $request->file('image');
        $id_img = $this->uploadServerImage($image);
        $referenceImage = $request->file('reference_image');
        $level = $request->input('level', 'l5'); // Default to l5

        // Send request to Picsart API
        $response = Http::withHeaders([
            'X-Picsart-API-Key' => $this->key,
            'Accept' => 'application/json',
        ])->attach(
            'image',
            file_get_contents($image->getRealPath()),
            $image->getClientOriginalName()
        )->attach(
            'reference_image',
            file_get_contents($referenceImage->getRealPath()),
            $referenceImage->getClientOriginalName()
        )->post('https://api.picsart.io/tools/1.0/styletransfer', [
            [
                'name' => 'level',
                'contents' => $level
            ],
            [
                'name' => 'format',
                'contents' => 'JPG'
            ]
        ]);

        // Check response status
        if ($response->successful()) {
            $data = $response->json();
            $image_url = $data['data']['url'];
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $folder = 'Styletransfer';
            $code_profile = 'image-' . time();
            $cdnUrl = $this->uploadToCloudFlareFromFile($image_url, $code_profile, $folder, $filename);
            activity('disneyCharators')
                ->withProperties([
                    'id_img' => $id_img,
                    'cdnUrl' => $cdnUrl,
                    'image_size' => $image->getSize(),
                    'api_url' => 'https://api.picsart.io/tools/1.0/styletransfer'
                ])
                ->log('Processed image for Disney characters transformation');
            $this->createActivities($id_img, $cdnUrl, $image->getSize(), '/api/disney_charactors', 'https://api.picsart.io/tools/1.0/styletransfer');
            return response()->json(['status' => true, 'url' => $cdnUrl]);
        } else {
            // Log activity on failure
            activity('disneyCharators')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to process image for Disney characters transformation');

            return response()->json(['status' => 'error', 'message' => 'Failed to process image', 'error' => $response->body()], 400);
        }
    }

    public function setup_profile_picture(Request $request){
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
            'id_size'=>'nullable',
            'effect'=>'nullable',
            'slug'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }
        $file = $request->file('image');
        $result = $this->uploadImage($file);
        $image_id = $result['id'];
        $routePath = $request->path();
        $result = Features::where('slug', $request->slug)->first();
        $feature=Features::where('slug', $request->slug)->first();
        if(!$result){
            $result =SubFeatures::where('slug', $request->slug)->first();
            $feature=SubFeatures::where('slug',$request->slug)->first();
        }
        if($result->is_pro==1 && $this->pro_account==false){
            return response()->json(['status'=>false,'error'=>'Not accepted'],401);
        }
        $initImageId = $result->initImageId;
        if($request->has('id_size')){
            $check=FeaturesSizes::where([
                'feature_id'=>$feature->id,
                'size_id'=>$request->id_size
            ])->first();
            if(!$check){
                $featuresId = $result->id;
                $folder = 'cartoon';
                $filename =  pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $id_img = $this->uploadServerImage($file);
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->leo_key,
                    'Accept' => 'application/json',
                ])->post('https://cloud.leonardo.ai/api/rest/v1/generations', [
                    'modelId' => $result->model_id,
                    'prompt' => $result->prompt,
                    'presetStyle' => $result->presetStyle,
                    'num_images' => 1,
                    'alchemy' => true,
                    isset($initImageId) && $initImageId !== null ? [
                        'controlnets' => [
                            [
                                'initImageId' => $initImageId,
                                'initImageType' => 'UPLOADED',
                                'preprocessorId' => (int) $result->preprocessorId,
                                'strengthType' => 'High',
                            ]
                        ]
                    ] : [],
                    "init_image_id" => $image_id,
                    "init_strength" => 0.5,
                ]);
                if ($response->successful()) {
                    $data = $response->body();
                    $data = json_decode($data, true);
                    $generationId = $data['sdGenerationJob']['generationId'];
                    while (true) {
                        $response = Http::withHeaders([
                            'accept' => 'application/json',
                            'authorization' =>'Bearer ' . $this->leo_key,
                        ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/'.$generationId);

                        if ($response->successful()) {
                            $data = $response->json();
                            if (!empty($data['generations_by_pk']['generated_images'])) {
                                // Get the original image URL and upload it to Cloudflare
                                $firstImageUrl = $data['generations_by_pk']['generated_images'][0]['url'];
                                $originalImageUrl = $this->uploadToCloudFlareFromCdn(
                                     $data['generations_by_pk']['generated_images'][0]['url'],
                                    'image-result' . time(),
                                    $feature->slug,
                                    Auth::guard('customer')->id() . '-gen' . $generationId
                                );
                                // By default, set $image to $originalImageUrl
                                $image = $originalImageUrl;
                                // Check if background removal is enabled
                                if ($feature->remove_bg == 1) {
                                    $imageWithoutBg = $this->removeBackground($originalImageUrl);
                                    $image = $this->uploadToCloudFlareFromCdn(
                                        $imageWithoutBg,
                                        'image-' . time(),
                                        $feature->slug,
                                        Auth::guard('customer')->id() . 'result-gen' . $generationId
                                    );
                                }
                                $effect= $request->effect ?? 'cyber2';
                                $image = $this->createEffect($image,$effect);
                                // Log the activity with the final image URL
                                Activities::create([
                                    'customer_id' => Auth::guard('customer')->id(),
                                    'photo_id' => $id_img,
                                    'features_id' => $featuresId,
                                    'image_result' => $image,
                                    'image_size' => $result->width,
                                    'ai_model' => 'Leo AI',
                                    'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                                ]);

                                // Return the JSON response with both the original and modified URLs
                                if($feature->remove_bg == 1){
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,              // Final image URL (with or without background removed)
                                        'bg_url' => $originalImageUrl  // Original image URL
                                    ]);
                                }else{
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
                }
            }
    }
    }
    public function fullBodyCartoon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $image = $request->file('image');
        $id_img = $this->uploadServerImage($image);
        $effectName = 'animation';

        // Send request to Picsart API
        $response = Http::withHeaders([
            'X-Picsart-API-Key' => $this->key,
            'Accept' => 'application/json',
        ])->attach(
            'image',
            file_get_contents($image->getRealPath()),
            $image->getClientOriginalName()
        )->post('https://api.picsart.io/tools/1.0/effects/ai', [
            [
                'name' => 'effect_name',
                'contents' => $effectName
            ],
            [
                'name' => 'format',
                'contents' => 'JPG'
            ]
        ]);

        // Check response status
        if ($response->successful()) {
            $data = $response->json();
            $image_url = $data['data']['url'];
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $folder = 'AIEffects';
            $code_profile = 'image-' . time();
            $cdnUrl = $this->uploadToCloudFlareFromFile($image_url, $code_profile, $folder, $filename);
            activity('fullBodyCartoon')
                ->withProperties([
                    'id_img' => $id_img,
                    'cdnUrl' => $cdnUrl,
                    'image_size' => $image->getSize(),
                    'api_url' => 'https://api.picsart.io/tools/1.0/effects/ai'
                ])
                ->log('Processed image for full-body cartoon effect');
            $this->createActivities($id_img, $cdnUrl, $image->getSize(), '/api/fullbody_cartoon', 'https://api.picsart.io/tools/1.0/effects/ai');
            return response()->json(['status' => true, 'url' => $cdnUrl]);
        } else {
            // Log activity on failure
            activity('fullBodyCartoon')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to process image for full-body cartoon effect');
            return response()->json(['status' => 'error', 'message' => 'Failed to process image', 'error' => $response->body()], 400);
        }
    }

    // public function animalToon(Request $request)
    // {
    //     return response()->json(['status' => 'develop']);
    // }

    public function newProfilePic(Request $request)
    {
        return response()->json(['status' => 'develop']);
    }

    public function createEffect($image,$effect)
    {
        $response = Http::withHeaders([
            'X-Picsart-API-Key' => $this->key,
            'Accept' => 'application/json',
        ])->asMultipart()->post('https://api.picsart.io/tools/1.0/effects', [
            [
                'name' => 'effect_name',
                'contents' => $effect,
            ],
            [
                'name' => 'format',
                'contents' => 'JPG',
            ],
            [
                'name' => 'image_url',
                'contents' => $image,
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['data']['url'])) {
                $feature = Features::where('slug', 'new-profile-picture')->first();
                $imageWithoutBg = $data['data']['url'];

                $image = $this->uploadToCloudFlareFromCdn(
                    $imageWithoutBg,
                    'image-' . time(),
                    $feature->slug,
                    Auth::guard('customer')->id() . 'result-gen-profile'
                );

                return $image;
            } else {
                return response()->json(['error' => 'No data found in response.'], 404);
            }
        } else {
            return response()->json([
                'error' => 'Failed to retrieve effects from Picsart API.',
                'details' => $response->json(),
                'status' => $response->status()
            ], $response->status());
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function cartoon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
            'slug'=>'required',
            'id_size'=>'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }
        $file = $request->file('image');
        $result = $this->uploadImage($file);
        $image_id = $result['id'];
        $routePath = $request->path();
        $result = Features::where('slug', $request->slug)->first();
        $feature=Features::where('slug', $request->slug)->first();
        if(!$result){
            $result =SubFeatures::where('slug', $request->slug)->first();
            $feature=SubFeatures::where('slug', $request->slug)->first();
        }
        if($result->is_pro==1 && $this->pro_account==false){
            return response()->json(['status'=>false,'error'=>'Not accepted'],401);
        }
        $initImageId = $result->initImageId;
        if($request->has('id_size')){
            $check=FeaturesSizes::where([
                'feature_id'=>$feature->id,
                'size_id'=>$request->id_size
            ])->first();
            if(!$check){
                $featuresId = $result->id;
                $folder = 'cartoon';
                $filename =  pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $id_img = $this->uploadServerImage($file);
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->leo_key,
                    'Accept' => 'application/json',
                ])->post('https://cloud.leonardo.ai/api/rest/v1/generations', [
                    'modelId' => $result->model_id,
                    'prompt' => $result->prompt,
                    'presetStyle' => $result->presetStyle,
                    'num_images' => 1,
                    'alchemy' => true,
                    isset($initImageId) && $initImageId !== null ? [
                        'controlnets' => [
                            [
                                'initImageId' => $initImageId,
                                'initImageType' => 'UPLOADED',
                                'preprocessorId' => (int) $result->preprocessorId,
                                'strengthType' => 'High',
                            ]
                        ]
                    ] : [],
                    "init_image_id" => $image_id,
                    "init_strength" => 0.5,
                ]);
                if ($response->successful()) {
                    $data = $response->body();
                    $data = json_decode($data, true);
                    $generationId = $data['sdGenerationJob']['generationId'];
                    while (true) {
                        $response = Http::withHeaders([
                            'accept' => 'application/json',
                            'authorization' =>'Bearer ' . $this->leo_key,
                        ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/'.$generationId);

                        if ($response->successful()) {
                            $data = $response->json();
                            if (!empty($data['generations_by_pk']['generated_images'])) {
                                // Get the original image URL and upload it to Cloudflare
                                $firstImageUrl = $data['generations_by_pk']['generated_images'][0]['url'];
                                $originalImageUrl = $this->uploadToCloudFlareFromCdn(
                                     $data['generations_by_pk']['generated_images'][0]['url'],
                                    'image-result' . time(),
                                    $feature->slug,
                                    Auth::guard('customer')->id() . '-gen' . $generationId
                                );
                                // By default, set $image to $originalImageUrl
                                $image = $originalImageUrl;
                                // Check if background removal is enabled
                                if ($feature->remove_bg == 1) {
                                    $imageWithoutBg = $this->removeBackground($originalImageUrl);
                                    $image = $this->uploadToCloudFlareFromCdn(
                                        $imageWithoutBg,
                                        'image-' . time(),
                                        $feature->slug,
                                        Auth::guard('customer')->id() . 'result-gen' . $generationId
                                    );
                                }
                                // Log the activity with the final image URL
                                Activities::create([
                                    'customer_id' => Auth::guard('customer')->id(),
                                    'photo_id' => $id_img,
                                    'features_id' => $featuresId,
                                    'image_result' => $image,
                                    'image_size' => $result->width,
                                    'ai_model' => 'Leo AI',
                                    'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                                ]);

                                // Return the JSON response with both the original and modified URLs
                                if($feature->remove_bg == 1){
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,              // Final image URL (with or without background removed)
                                        'bg_url' => $originalImageUrl  // Original image URL
                                    ]);
                                }else{
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
                }
            }else{
                $size=ImageSize::where('id',$request->id_size)->first();
                $height=$size->height;
                $width=$size->width;
                $featuresId = $result->id;
                $folder = 'cartoon';
                $filename =  pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $id_img = $this->uploadServerImage($file);
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->leo_key,
                    'Accept' => 'application/json',
                ])->post('https://cloud.leonardo.ai/api/rest/v1/generations', [
                    'height' => $height,
                    'modelId' => $result->model_id,
                    'prompt' => $result->prompt,
                    'presetStyle' => $result->presetStyle,
                    'width' => $width,
                    'num_images' => 1,
                    'alchemy' => true,
                    isset($initImageId) && $initImageId !== null ? [
                        'controlnets' => [
                            [
                                'initImageId' => $initImageId,
                                'initImageType' => 'UPLOADED',
                                'preprocessorId' => (int) $result->preprocessorId,
                                'strengthType' => 'High',
                            ]
                        ]
                    ] : [],
                    "init_image_id" => $image_id,
                    "init_strength" => 0.5,
                ]);
                if ($response->successful()) {
                    $data = $response->body();
                    $data = json_decode($data, true);
                    $generationId = $data['sdGenerationJob']['generationId'];
                    while (true) {
                        $response = Http::withHeaders([
                            'accept' => 'application/json',
                            'authorization' =>'Bearer ' . $this->leo_key,
                        ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/'.$generationId);

                        if ($response->successful()) {
                            $data = $response->json();
                            if (!empty($data['generations_by_pk']['generated_images'])) {
                                // Get the original image URL and upload it to Cloudflare
                                $firstImageUrl = $data['generations_by_pk']['generated_images'][0]['url'];
                                $originalImageUrl = $this->uploadToCloudFlareFromCdn(
                                     $data['generations_by_pk']['generated_images'][0]['url'],
                                    'image-result' . time(),
                                    $feature->slug,
                                    Auth::guard('customer')->id() . '-gen' . $generationId
                                );
                                // By default, set $image to $originalImageUrl
                                $image = $originalImageUrl;
                                // Check if background removal is enabled
                                if ($feature->remove_bg == 1) {
                                    $imageWithoutBg = $this->removeBackground($originalImageUrl);
                                    $image = $this->uploadToCloudFlareFromCdn(
                                        $imageWithoutBg,
                                        'image-' . time(),
                                        $feature->slug,
                                        Auth::guard('customer')->id() . 'result-gen' . $generationId
                                    );
                                }
                                // Log the activity with the final image URL
                                Activities::create([
                                    'customer_id' => Auth::guard('customer')->id(),
                                    'photo_id' => $id_img,
                                    'features_id' => $featuresId,
                                    'image_result' => $image,
                                    'image_size' => $result->width,
                                    'ai_model' => 'Leo AI',
                                    'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                                ]);

                                // Return the JSON response with both the original and modified URLs
                                if($feature->remove_bg == 1){
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,              // Final image URL (with or without background removed)
                                        'bg_url' => $originalImageUrl  // Original image URL
                                    ]);
                                }else{
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
                }
            }

        }else{
            $initImageId=$result->initImageId;
            $featuresId = $result->id;
            $folder = 'cartoon';
            $filename =  pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $id_img = $this->uploadServerImage($file);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->leo_key,
                'Accept' => 'application/json',
            ])->post('https://cloud.leonardo.ai/api/rest/v1/generations', [
                'modelId' => $result->model_id,
                'prompt' => $result->prompt,
                'presetStyle' => $result->presetStyle,
                'num_images' => 1,
                'alchemy' => true,
                isset($initImageId) && $initImageId !== null ? [
                    'controlnets' => [
                        [
                            'initImageId' => $initImageId,
                            'initImageType' => 'UPLOADED',
                            'preprocessorId' => (int) $result->preprocessorId,
                            'strengthType' => 'High',
                        ]
                    ]
                ] : [],
                "init_image_id" => $image_id,
                "init_strength" => 0.5,
            ]);
            if ($response->successful()) {
                $data = $response->body();
                $data = json_decode($data, true);
                $generationId = $data['sdGenerationJob']['generationId'];
                while (true) {
                    $response = Http::withHeaders([
                        'accept' => 'application/json',
                        'authorization' =>'Bearer ' . $this->leo_key,
                    ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/'.$generationId);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (!empty($data['generations_by_pk']['generated_images'])) {
                            // Get the original image URL and upload it to Cloudflare
                            $firstImageUrl = $data['generations_by_pk']['generated_images'][0]['url'];
                            $originalImageUrl = $this->uploadToCloudFlareFromCdn(
                                 $data['generations_by_pk']['generated_images'][0]['url'],
                                'image-result' . time(),
                                $feature->slug,
                                Auth::guard('customer')->id() . '-gen' . $generationId
                            );
                            // By default, set $image to $originalImageUrl
                            $image = $originalImageUrl;
                            // Check if background removal is enabled
                            if ($feature->remove_bg == 1) {
                                $imageWithoutBg = $this->removeBackground($originalImageUrl);
                                $image = $this->uploadToCloudFlareFromCdn(
                                    $imageWithoutBg,
                                    'image-' . time(),
                                    $feature->slug,
                                    Auth::guard('customer')->id() . 'result-gen' . $generationId
                                );
                            }
                            // Log the activity with the final image URL
                            Activities::create([
                                'customer_id' => Auth::guard('customer')->id(),
                                'photo_id' => $id_img,
                                'features_id' => $featuresId,
                                'image_result' => $image,
                                'image_size' => $result->width,
                                'ai_model' => 'Leo AI',
                                'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                            ]);

                            // Return the JSON response with both the original and modified URLs
                            if($feature->remove_bg == 1){
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,              // Final image URL (with or without background removed)
                                    'bg_url' => $originalImageUrl  // Original image URL
                                ]);
                            }else{
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,
                                ]);
                            }
                        }
                    }
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
            }
        }
    }
    /**
     * Show the form for creating a new resource.
     */

    public function uploadImage($file)
    {
        $this->uploadServerImage($file);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->leo_key,
            'Accept' => 'application/json',
        ])->post('https://cloud.leonardo.ai/api/rest/v1/init-image', [
            'extension' => 'png'
        ]);
        if ($response->successful()) {
            $data = $response->json();
            $fields = json_decode($data['uploadInitImage']['fields'], true);
            $contentType = $fields['Content-Type'];
            $bucket = $fields['bucket'];
            $algorithm = $fields['X-Amz-Algorithm'];
            $credential = $fields['X-Amz-Credential'];
            $date = $fields['X-Amz-Date'];
            $securityToken = $fields['X-Amz-Security-Token'];
            $key = $data['uploadInitImage']['key'];
            $policy = $fields['Policy'];
            $url = $data['uploadInitImage']['url'];
            $id = $data['uploadInitImage']['id'];
            $signature = $fields['X-Amz-Signature'];
            $response = Http::asMultipart()->post($url, [
                ['name' => 'Content-Type', 'contents' => $contentType],
                ['name' => 'bucket', 'contents' => $bucket],
                ['name' => 'X-Amz-Algorithm', 'contents' => $algorithm],
                ['name' => 'X-Amz-Credential', 'contents' => $credential],
                ['name' => 'X-Amz-Date', 'contents' => $date],
                ['name' => 'X-Amz-Security-Token', 'contents' => $securityToken],
                ['name' => 'key', 'contents' => $key],
                ['name' => 'Policy', 'contents' => $policy],
                ['name' => 'X-Amz-Signature', 'contents' => $signature],
                ['name' => 'file', 'contents' => fopen($file->getPathname(), 'r'), 'filename' => $file->getClientOriginalName()],
            ]);

            if ($response->status() === 204) {
                return [
                    'url' => $this->get_leo_image_url($id),
                    'id' => $id
                ];
            } else {
                return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
            }
        } else {
            return $response->body();
        }
    }
    public function get_leo_image_url($id)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->leo_key,
            'Accept' => 'application/json',
        ])->get('https://cloud.leonardo.ai/api/rest/v1/init-image/' . $id);
        if ($response->successful()) {
            $data = $response->json();
            return $data['init_images_by_pk']['url'];
        }
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
