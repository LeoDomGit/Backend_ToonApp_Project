<?php

namespace App\Http\Controllers;

use App\Models\Activities;
use App\Models\Background;
use App\Models\FeatureImage;
use App\Models\Features;
use App\Models\SubFeatures;
use App\Models\FeaturesSizes;
use App\Models\ImageSize;
use App\Models\Effects;
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
    protected $vancekey;
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
        $result = Key::where('api', 'picsart')->where('key', '!=', '0')->orderBy('id', 'asc')->first();
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://genai-api.picsart.io/v1/balance', [
            'headers' => [
                'X-Picsart-API-Key' => $result->key,
                'accept' => 'application/json',
            ],
        ]);
        $body = json_decode($response->getBody(), true);
        $credits = isset($body['credits']) ? $body['credits'] : 0;
        if ($credits < 5) {
            $result->update(['key' => 0]);
            $newKey = Key::where('api', 'picsart')->where('key', '!=', 0)->orderBy('id', 'asc')->first();
            if ($newKey) {
                $this->key = $newKey->key;
            } else {
                $this->key = null;
            }
        } else {
            $this->key = $result->key;
        }

        $max_num = 20;
        $used_num = 0;
        while ($used_num <= $max_num) {
            $vance_key = Key::where('api', 'vance')->where('key','!=','')
                ->first();
            if (!$vance_key) {
                $this->vancekey = null;
                break;
            }
            $responseVance = $client->request('GET', 'https://api-service.vanceai.com/web_api/v1/point?api_token=' . $vance_key->key);
            $bodyVance = json_decode($responseVance->getBody(), true);
            $max_num = isset($bodyVance['data']['max_num']) ? $bodyVance['data']['max_num'] : 0;
            $used_num = isset($bodyVance['data']['used_num']) ? $bodyVance['data']['used_num'] : 0;
            if ($used_num == $max_num) {
                $vance_key->update(['key'=>'']);
                continue;
            }
            $this->vancekey = $vance_key->key;
            break;
        }

        $this->leo_key = env('IMAGE_API_KEY');
        $this->aws_secret_key = 'b52dcdbea046cc2cc13a5b767a1c71ea8acbe96422b3e45525d3678ce2b5ed3e';
        $this->aws_access_key = 'cbb3e2fea7c7f3e7af09b67eeec7d62c';
        $this->client = new Client();
        $guard = Auth::guard('customer');
        $user = $guard->user();
        $bearerToken = $request->bearerToken();

        if ($bearerToken && $bearerToken === config('app.access_token')) {
            $this->pro_account = true;
        } else {
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
    public function getEffect(Request $request)
    {
        $data = Effects::active()->get();
        return response()->json($data);
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

        $image = $request->file('image');
        $imageContent = file_get_contents($image);
        $tempFilePath = storage_path('app/public/anime/temp_image.jpg');
        file_put_contents($tempFilePath, $imageContent);
        $routePath = $request->path();
        $feature = Features::where('api_endpoint', $routePath)->first();
        if ($feature->is_pro == 1 && $this->pro_account == false) {
            return response()->json(['status' => false, 'error' => 'Not accepted'], 401);
        }
        if ($request->has('background')) {
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
                'bg_image_url' => $request->background
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
                    Auth::guard('customer')->id() . 'result-gen' . time()
                );
                return response()->json(['url' => $image]);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Failed to process image', 'error' => $response->json()], 400);
            }
        } else {
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
                    Auth::guard('customer')->id() . 'result-gen' . time()
                );
                return response()->json(['url' => $image]);
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
        $finalFileName = time().'.png';
        $r2object = $folder . '/' . $finalFileName;

        // Step 3: Upload the file to Cloudflare R2
        try {
            $result = $s3Client->putObject([
                'Bucket' => $r2bucket,
                'Key' => $r2object,
                'Body' => file_get_contents($file->getRealPath()), // Get the file content
                'ContentType' => $file->getMimeType(),
            ]);

            // Generate the CDN URL using the custom domain
            $cdnUrl = "https://artapp.promptme.info/$folder/$finalFileName";
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
private function uploadToCloudFlareFromCdn($cdnUrl, $folder, $filename)
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

        // Step 2: Fetch the image from the CDN URL
        $imageContent = file_get_contents($cdnUrl);
        if ($imageContent === false) {
            Log::error("Failed to fetch the image from CDN URL: $cdnUrl");
            return 'error: failed to fetch the image';
        }

        $r2object = $folder . '/' . $filename;

        // Step 4: Upload the file to Cloudflare R2
        try {
            $result = $s3Client->putObject([
                'Bucket' => $r2bucket,
                'Key' => $r2object,
                'Body' => $imageContent, // Use the fetched image content
                'ContentType' => 'image/png',
            ]);

            // Generate the CDN URL using the custom domain
            $newCdnUrl = "https://artapp.promptme.info/$folder/$filename";
            return $newCdnUrl;
        } catch (S3Exception $e) {
            Log::error("Error uploading file: " . $e->getMessage());
            return 'error: ' . $e->getMessage();
        }
    } catch (\Throwable $th) {
        Log::error($th->getMessage());
        return 'error: ' . $th->getMessage();
    }
}

    public function removeBackground($image)
    {
        $response = Http::withHeaders([
            'X-Picsart-API-Key' => $this->key,
            'Accept' => 'application/json',
        ])->asMultipart()->post('https://api.picsart.io/tools/1.0/removebg', [
            [
                'name' => 'output_type',
                'contents' => 'cutout',
            ],
            [
                'name' => 'bg_blur',
                'contents' => '0',
            ],
            [
                'name' => 'scale',
                'contents' => 'fit',
            ],
            [
                'name' => 'auto_center',
                'contents' => 'false',
            ],
            [
                'name' => 'stroke_size',
                'contents' => '0',
            ],
            [
                'name' => 'stroke_color',
                'contents' => 'FFFFFF',
            ],
            [
                'name' => 'stroke_opacity',
                'contents' => '100',
            ],
            [
                'name' => 'shadow',
                'contents' => 'disabled',
            ],
            [
                'name' => 'shadow_opacity',
                'contents' => '20',
            ],
            [
                'name' => 'shadow_blur',
                'contents' => '50',
            ],
            [
                'name' => 'format',
                'contents' => 'PNG',
            ],
            [
                'name' => 'image_url',
                'contents' => $image,
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $image_url=$data['data']['url'];
            $folder='RemoveBackground';
            $filename=time().'.png';
            return $this->uploadToCloudFlareFromCdn($image_url,$folder,$filename);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process image',
                'error' => $response->json(),
            ], 400);
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
    private function uploadToCloudFlareFromUrl($imageFileUrl, $folder, $filename)
    {
        try {
            $headers = @get_headers($imageFileUrl, 1); // Sử dụng tham số 1 để lấy dạng mảng
            $contentType = $headers && isset($headers['Content-Type']) ? $headers['Content-Type'] : 'image/jpeg';

            $extension = pathinfo(parse_url($imageFileUrl, PHP_URL_PATH), PATHINFO_EXTENSION);

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
            $r2object = $folder . '/' . $filename . '.' . $extension;
            Log::debug('uploadToCloudFlareFromUrl: ' . $r2object);
            $imageData = @file_get_contents($imageFileUrl);

            // Step 4: Upload the file to Cloudflare R2
            try {
                $result = $s3Client->putObject([
                    'Bucket' => $r2bucket,
                    'Key' => $r2object,
                    'Body' => $imageData,
                    'ContentType' => $contentType,
                ]);

                // Generate the CDN URL using the custom domain
                $cdnUrl = "https://artapp.promptme.info/$folder/$filename.$extension";
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
            'slug' => 'required',
            'id_size' => 'nullable|exists:image_sizes,id',
            'pro_acc' => 'required|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }
        $file = $request->file('image');
        $result = $this->uploadImage($file);
        $image_id = $result['id'];
        $result = Features::where('slug', $request->slug)->first();
        $feature = Features::where('slug', $request->slug)->first();
        if ($result) {
            $id_feature = $feature->id;
        }
        if (!$result) {
            $result = SubFeatures::where('slug', $request->slug)->first();
            $feature = SubFeatures::where('slug', $request->slug)->first();
            $id_feature = $feature->feature_id;
        }

        if ($result->is_pro == 1 && $request->pro_acc == 0) {
            return response()->json(['status' => false, 'error' => 'Not accepted'], 401);
        }
        $initImageId = $result->initImageId;
        if ($request->has('id_size')) {
            $check = FeaturesSizes::where([
                'feature_id' => $id_feature,
                'size_id' => $request->id_size
            ])->first();
            if (!$check) {
                return response()->json(['status' => 'error', 'message' => 'Size này không được hỗ trợ trong feature'], 400);
            }
            $size = ImageSize::where('id', $request->id_size)->first();
            $height = $size->height;
            $width = $size->width;
            $initImageId = $result->initImageId;
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
                            'strengthType' => $result->strengthType,
                            'weight' => $result->weight,

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
                        'authorization' => 'Bearer ' . $this->leo_key,
                    ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

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
                            if ($feature->remove_bg == 1) {
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,              // Final image URL (with or without background removed)
                                    'bg_url' => $originalImageUrl  // Original image URL
                                ]);
                            } else {
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
        } else {
            $initImageId = $result->initImageId;
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
                            'strengthType' => $result->strengthType,
                            'weight' => $result->weight,

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
                        'authorization' => 'Bearer ' . $this->leo_key,
                    ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

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
                            if ($feature->remove_bg == 1) {
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,              // Final image URL (with or without background removed)
                                    'bg_url' => $originalImageUrl  // Original image URL
                                ]);
                            } else {
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

    public function setup_profile_picture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|mimes:png,jpg,jpeg',
            'id_size' => 'nullable',
            'effect' => 'nullable',
            'slug' => 'required',
            'image_url' => 'nullable|url',
            'pro_acc' => 'required|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }
        $feature = $this->getFeatureBySlug($request->slug);
        if (!$feature) {
            return response()->json(['status' => 'error', 'message' => 'Feature not found'], 404);
        }

        if ($feature->is_pro == 1 && $request->pro_acc == 0) {
            return response()->json(['status' => false, 'error' => 'Not accepted'], 401);
        }
        if ($request->has('image_url')) {
            $effect = $request->effect ?? 'cyber2';
            $image = $request->image_url;
            $image = $this->createEffect($image, $effect);
            return response()->json([
                'status' => 'success',
                'url' => $image,
                'style_url' => $request->style_url,
            ]);
        }
        $file = $request->file('image');
        $result = $this->uploadImage($file);
        $image_id = $result['id'];
        $routePath = $request->path();
        $result = Features::where('slug', $request->slug)->first();
        $feature = Features::where('slug', $request->slug)->first();
        if (!$result) {
            $result = SubFeatures::where('slug', $request->slug)->first();
            $feature = SubFeatures::where('slug', $request->slug)->first();
        }
        if ($result->is_pro == 1 && $this->pro_account == false) {
            return response()->json(['status' => false, 'error' => 'Not accepted'], 401);
        }
        $initImageId = $result->initImageId;
        if ($request->has('id_size')) {
            $check = FeaturesSizes::where([
                'feature_id' => $feature->id,
                'size_id' => $request->id_size
            ])->first();
            if (!$check) {
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
                                'strengthType' => $result->strengthType,
                                'weight' => $result->weight,
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
                            'authorization' => 'Bearer ' . $this->leo_key,
                        ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

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
                                $effect = $request->effect ?? 'cyber2';
                                $image = $this->createEffect($image, $effect);
                                // Log the activity with the final image URL
                                Activities::create([
                                    'customer_id' => Auth::guard('customer')->id(),
                                    'photo_id' => $id_img,
                                    'features_id' => $featuresId,
                                    'image_result' => $image,
                                    'image_size' => $result->width,
                                    'ai_model' => 'Leo AI',
                                    'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                                    'attributes' => json_encode([
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
                                                    'strengthType' => $result->strengthType,
                                                    'weight' => $result->weight,

                                                ]
                                            ]
                                        ] : [],
                                        "init_image_id" => $image_id,
                                        "init_strength" => 0.5,
                                    ]),
                                    'request' => json_encode($request->all()),
                                ]);

                                // Return the JSON response with both the original and modified URLs
                                if ($feature->remove_bg == 1) {
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,              // Final image URL (with or without background removed)
                                        'style_url' => $originalImageUrl  // Original image URL
                                    ]);
                                } else {
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,
                                        'style_url' => $originalImageUrl
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
                }
            } else {
                $size = ImageSize::where('id', $request->id_size)->first();
                $height = $size->height;
                $width = $size->width;
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
                                'strengthType' => $result->strengthType,
                                'weight' => $result->weight,

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
                            'authorization' => 'Bearer ' . $this->leo_key,
                        ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

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
                                $effect = $request->effect ?? 'cyber2';
                                $image = $this->createEffect($image, $effect);
                                // Log the activity with the final image URL
                                Activities::create([
                                    'customer_id' => Auth::guard('customer')->id(),
                                    'photo_id' => $id_img,
                                    'features_id' => $featuresId,
                                    'image_result' => $image,
                                    'image_size' => $result->width,
                                    'ai_model' => 'Leo AI',
                                    'attributes' => json_encode([
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
                                                    'strengthType' => $result->strengthType,
                                                    'weight' => $result->weight,

                                                ]
                                            ]
                                        ] : [],
                                        "init_image_id" => $image_id,
                                        "init_strength" => 0.5,
                                    ]),
                                    'request' => json_encode($request->all()),
                                    'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                                ]);

                                // Return the JSON response with both the original and modified URLs
                                if ($feature->remove_bg == 1) {
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,              // Final image URL (with or without background removed)
                                        'style_url' => $originalImageUrl
                                    ]);
                                } else {
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,
                                        'style_url' => $originalImageUrl
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
                }
            }
        } else {
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
                            'strengthType' => $result->strengthType,
                            'weight' => $result->weight,

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
                        'authorization' => 'Bearer ' . $this->leo_key,
                    ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

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
                            $effect = $request->effect ?? 'cyber2';
                            $image = $this->createEffect($image, $effect);
                            // Log the activity with the final image URL
                            Activities::create([
                                'customer_id' => Auth::guard('customer')->id(),
                                'photo_id' => $id_img,
                                'features_id' => $featuresId,
                                'image_result' => $image,
                                'image_size' => $result->width,
                                'ai_model' => 'Leo AI',
                                'attributes' => json_encode([
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
                                                'strengthType' => $result->strengthType,
                                                'weight' => $result->weight,

                                            ]
                                        ]
                                    ] : [],
                                    "init_image_id" => $image_id,
                                    "init_strength" => 0.5,
                                ]),
                                'request' => json_encode($request->all()),
                                'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                            ]);

                            // Return the JSON response with both the original and modified URLs
                            if ($feature->remove_bg == 1) {
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,
                                    'style_url' => $originalImageUrl
                                ]);
                            } else {
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,
                                    'style_url' => $originalImageUrl
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

    public function createEffect($image, $effect)
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
                    Auth::guard('customer')->id() . 'result-gen-profile' . time()
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

    public function testDownLoad($id){
        $apiToken = $this->vancekey;
        $response = Http::post('https://api-service.vanceai.com/web_api/v1/download', [
            'api_token' => $apiToken,
            'trans_id' => $id,
        ]);
        if ($response->successful()) {
            $fileContent = $response->body(); // Get the image data
            $folder = 'uploadcartoon';
            $filename = 'transformed_' . uniqid(); // Generate a unique filename

            // Step 1: Save the binary content temporarily as a file
            $tempFilePath = storage_path('app/temp_transformed_image.jpg');
            file_put_contents($tempFilePath, $fileContent);
            $tempFile = new \Illuminate\Http\File($tempFilePath);
            return response()->json(['data'=>$tempFile]);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function testTransform($id){
        $apiToken = $this->vancekey;
        $transformResponse = Http::post('https://api-service.vanceai.com/web_api/v1/transform', [
            'api_token' => $apiToken,
            'uid' =>$id,
            'jconfig' => json_encode([
                'job' => 'animegan',
                'config' => [
                    'module' => 'animegan2',
                    'module_params' => [
                        'model_name' => 'Animegan2Stable',
                        'single_face' => true,
                        'denoising_strength' => 0.75,
                    ]
                ]
            ]),
        ]);

        if ($transformResponse->successful()) {
            // Retrieve the 'trans_id' from the transform response
            $transformData = $transformResponse->json();
            $transId = $transformData['data']['trans_id'];
            return $transId;
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function TestUpload(Request $request){
        $file = $request->file('image');

        // Generate a unique filename
        $filename = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->getPathname();

        // API Token (using Vance key set in the constructor)
        $apiToken = $this->vancekey;
        if (!$apiToken||$apiToken=='0'||$apiToken==0) {
            return response()->json(['error' => 'No valid API key available'], 500);
        }
        $response = Http::attach('file', file_get_contents($filePath), $filename)
            ->post('https://api-service.vanceai.com/web_api/v1/upload', [
                'api_token' => $apiToken,
            ]);
        if ($response->successful()) {
            // Retrieve the 'uid' from the response
            $data = $response->json();
            $uid = $data['data']['uid'];
            return $uid;
        }
    }
    // public function cartoon(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'image' => 'required|mimes:png,jpg,jpeg',
    //         'slug' => 'required',
    //         'id_size' => 'nullable',
    //          'pro_acc'=>'required|in:0,1'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
    //     }
    //     $file = $request->file('image');
    //     $result = $this->uploadImage($file);
    //     $image_id = $result['id'];
    //     $routePath = $request->path();
    //     $is_feature=true;
    //     $result = Features::where('slug', $request->slug)->first();
    //     $feature = Features::where('slug', $request->slug)->first();
    //     if (!$result) {
    //         $result = SubFeatures::where('slug', $request->slug)->first();
    //         $feature = SubFeatures::where('slug', $request->slug)->first();
    //         $is_feature=false;
    //     }
    //     if ($result->is_pro == 1 && $request->pro_acc == 0) {
    //         return response()->json(['status' => false, 'error' => 'Not accepted'], 401);
    //     }
    //     $initImageId = $result->initImageId;
    //     if ($request->has('id_size')) {
    //         $id_feature=0;
    //         if($is_feature==false){
    //             $id_feature=$feature->feature_id;
    //         }else{
    //             $id_feature=$feature->id;
    //         }
    //         $check = FeaturesSizes::where([
    //             'feature_id' => $id_feature,
    //             'size_id' => $request->id_size
    //         ])->first();
    //         if (!$check) {
    //             $featuresId = $result->id;
    //             $folder = 'cartoon';
    //             $filename =  pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    //             $id_img = $this->uploadServerImage($file);
    //             $response = Http::withHeaders([
    //                 'Authorization' => 'Bearer ' . $this->leo_key,
    //                 'Accept' => 'application/json',
    //             ])->post('https://cloud.leonardo.ai/api/rest/v1/generations', [
    //                 'modelId' => $result->model_id,
    //                 'prompt' => $result->prompt,
    //                 'presetStyle' => $result->presetStyle,
    //                 'num_images' => 1,
    //                 "width"=> 1024,
    //                 'alchemy' => true,
    //                 isset($initImageId) && $initImageId !== null ? [
    //                     'controlnets' => [
    //                         [
    //                             'initImageId' => $initImageId,
    //                             'initImageType' => 'UPLOADED',
    //                             'preprocessorId' => (int) $result->preprocessorId,
    //                             'strengthType' => $result->strengthType,
    //                             'weight' => $result->weight,
    //                         ]
    //                     ]
    //                 ] : [],
    //                 "init_image_id" => $image_id,
    //                 "init_strength" => 0.5,
    //             ]);
    //             if ($response->successful()) {
    //                 $data = $response->body();
    //                 $data = json_decode($data, true);
    //                 $generationId = $data['sdGenerationJob']['generationId'];
    //                 while (true) {
    //                     $response = Http::withHeaders([
    //                         'accept' => 'application/json',
    //                         'authorization' => 'Bearer ' . $this->leo_key,
    //                     ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

    //                     if ($response->successful()) {
    //                         $data = $response->json();
    //                         if (!empty($data['generations_by_pk']['generated_images'])) {
    //                             // Get the original image URL and upload it to Cloudflare
    //                             $firstImageUrl = $data['generations_by_pk']['generated_images'][0]['url'];
    //                             $originalImageUrl = $this->uploadToCloudFlareFromCdn(
    //                                 $data['generations_by_pk']['generated_images'][0]['url'],
    //                                 'image-result' . time(),
    //                                 $feature->slug,
    //                                 Auth::guard('customer')->id() . '-gen' . $generationId
    //                             );
    //                             // By default, set $image to $originalImageUrl
    //                             $image = $originalImageUrl;
    //                             // Check if background removal is enabled
    //                             if ($feature->remove_bg == 1) {
    //                                 $imageWithoutBg = $this->removeBackground($originalImageUrl);
    //                                 $image = $this->uploadToCloudFlareFromCdn(
    //                                     $imageWithoutBg,
    //                                     'image-' . time(),
    //                                     $feature->slug,
    //                                     Auth::guard('customer')->id() . 'result-gen' . $generationId
    //                                 );
    //                             }
    //                             // Log the activity with the final image URL
    //                             Activities::create([
    //                                 'customer_id' => Auth::guard('customer')->id(),
    //                                 'photo_id' => $id_img,
    //                                 'features_id' => $featuresId,
    //                                 'image_result' => $image,
    //                                 'image_size' => $result->width,
    //                                 'attributes'=>json_encode([
    //                                     'ai_model' => 'Leo AI',
    //                                     'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
    //                                     'modelId' => $result->model_id,
    //                                     'prompt' => $result->prompt,
    //                                     'presetStyle' => $result->presetStyle,
    //                                     'num_images' => 1,
    //                                     'alchemy' => true,
    //                                     'controlnets' => isset($initImageId) && $initImageId !== null ? [
    //                                         [
    //                                             'initImageId' => $initImageId,
    //                                             'initImageType' => 'UPLOADED',
    //                                             'preprocessorId' => (int) $result->preprocessorId,
    //                                             'strengthType' => $result->strengthType,
    //                             'weight' => $result->weight,
    //                                         ]
    //                                     ] : [],
    //                                     'init_image_id' => $image_id,
    //                                     'init_strength' => 0.5,
    //                                         ]),
    //                                     'request' => json_encode($request->all()),
    //                                 'ai_model' => 'Leo AI',
    //                                 'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
    //                             ]);

    //                             // Return the JSON response with both the original and modified URLs
    //                             if ($feature->remove_bg == 1) {
    //                                 return response()->json([
    //                                     'status' => true,
    //                                     'url' => $image,              // Final image URL (with or without background removed)
    //                                     'bg_url' => $originalImageUrl  // Original image URL
    //                                 ]);
    //                             } else {
    //                                 return response()->json([
    //                                     'status' => true,
    //                                     'url' => $image,
    //                                 ]);
    //                             }
    //                         }
    //                     }
    //                 }
    //             } else {
    //                 return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
    //             }
    //         } else {
    //             $size = ImageSize::where('id', $request->id_size)->first();
    //             $height = $size->height;
    //             $width = $size->width;
    //             $featuresId = $result->id;
    //             $folder = 'cartoon';
    //             $filename =  pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    //             $id_img = $this->uploadServerImage($file);
    //             $response = Http::withHeaders([
    //                 'Authorization' => 'Bearer ' . $this->leo_key,
    //                 'Accept' => 'application/json',
    //             ])->post('https://cloud.leonardo.ai/api/rest/v1/generations', [
    //                 'height' => $height,
    //                 'modelId' => $result->model_id,
    //                 'prompt' => $result->prompt,
    //                 'presetStyle' => $result->presetStyle,
    //                 'width' => $width,
    //                 'num_images' => 1,
    //                 'alchemy' => true,
    //                 isset($initImageId) && $initImageId !== null ? [
    //                     'controlnets' => [
    //                         [
    //                             'initImageId' => $initImageId,
    //                             'initImageType' => 'UPLOADED',
    //                             'preprocessorId' => (int) $result->preprocessorId,
    //                             'strengthType' => $result->strengthType,
    //                             'weight' => $result->weight,
    //                         ]
    //                     ]
    //                 ] : [],
    //                 "init_image_id" => $image_id,
    //                 "init_strength" => 0.5,
    //             ]);
    //             if ($response->successful()) {
    //                 $data = $response->body();
    //                 $data = json_decode($data, true);
    //                 $generationId = $data['sdGenerationJob']['generationId'];
    //                 while (true) {
    //                     $response = Http::withHeaders([
    //                         'accept' => 'application/json',
    //                         'authorization' => 'Bearer ' . $this->leo_key,
    //                     ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

    //                     if ($response->successful()) {
    //                         $data = $response->json();
    //                         if (!empty($data['generations_by_pk']['generated_images'])) {
    //                             // Get the original image URL and upload it to Cloudflare
    //                             $firstImageUrl = $data['generations_by_pk']['generated_images'][0]['url'];
    //                             $originalImageUrl = $this->uploadToCloudFlareFromCdn(
    //                                 $data['generations_by_pk']['generated_images'][0]['url'],
    //                                 'image-result' . time(),
    //                                 $feature->slug,
    //                                 Auth::guard('customer')->id() . '-gen' . $generationId
    //                             );
    //                             // By default, set $image to $originalImageUrl
    //                             $image = $originalImageUrl;
    //                             // Check if background removal is enabled
    //                             if ($feature->remove_bg == 1) {
    //                                 $imageWithoutBg = $this->removeBackground($originalImageUrl);
    //                                 $image = $this->uploadToCloudFlareFromCdn(
    //                                     $imageWithoutBg,
    //                                     'image-' . time(),
    //                                     $feature->slug,
    //                                     Auth::guard('customer')->id() . 'result-gen' . $generationId
    //                                 );
    //                             }
    //                             // Log the activity with the final image URL
    //                             Activities::create([
    //                                 'customer_id' => Auth::guard('customer')->id(),
    //                                 'photo_id' => $id_img,
    //                                 'features_id' => $featuresId,
    //                                 'image_result' => $image,
    //                                 'image_size' => $result->width,
    //                                 'attributes'=>json_encode([
    //                                     'height' => $height,
    //                                     'modelId' => $result->model_id,
    //                                     'prompt' => $result->prompt,
    //                                     'presetStyle' => $result->presetStyle,
    //                                     'width' => $width,
    //                                     'num_images' => 1,
    //                                     'alchemy' => true,
    //                                     isset($initImageId) && $initImageId !== null ? [
    //                                         'controlnets' => [
    //                                             [
    //                                                 'initImageId' => $initImageId,
    //                                                 'initImageType' => 'UPLOADED',
    //                                                 'preprocessorId' => (int) $result->preprocessorId,
    //                                                 'strengthType' => $result->strengthType,
    //                                                 'weight' => $result->weight,
    //                                             ]
    //                                         ]
    //                                     ] : [],
    //                                     "init_image_id" => $image_id,
    //                                     "init_strength" => 0.5,
    //                                 ]),
    //                                 'request' => json_encode($request->all()),
    //                                 'ai_model' => 'Leo AI',
    //                                 'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
    //                             ]);
    //                             // Return the JSON response with both the original and modified URLs
    //                             if ($feature->remove_bg == 1) {
    //                                 return response()->json([
    //                                     'status' => true,
    //                                     'url' => $image,              // Final image URL (with or without background removed)
    //                                     'bg_url' => $originalImageUrl  // Original image URL
    //                                 ]);
    //                             } else {
    //                                 return response()->json([
    //                                     'status' => true,
    //                                     'url' => $image,
    //                                 ]);
    //                             }
    //                         }
    //                     }
    //                 }
    //             } else {
    //                 return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
    //             }
    //         }
    //     } else {
    //         $initImageId = $result->initImageId;
    //         $featuresId = $result->id;
    //         $folder = 'cartoon';
    //         $filename =  pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    //         $id_img = $this->uploadServerImage($file);
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $this->leo_key,
    //             'Accept' => 'application/json',
    //         ])->post('https://cloud.leonardo.ai/api/rest/v1/generations', [
    //             'modelId' => $result->model_id,
    //             'prompt' => $result->prompt,
    //             'presetStyle' => $result->presetStyle,
    //             'num_images' => 1,
    //             'alchemy' => true,
    //             isset($initImageId) && $initImageId !== null ? [
    //                 'controlnets' => [
    //                     [
    //                         'initImageId' => $initImageId,
    //                         'initImageType' => 'UPLOADED',
    //                         'preprocessorId' => (int) $result->preprocessorId,
    //                         'strengthType' => $result->strengthType,
    //                         'weight' => $result->weight,
    //                     ]
    //                 ]
    //             ] : [],
    //             "init_image_id" => $image_id,
    //             "init_strength" => 0.5,
    //         ]);
    //         if ($response->successful()) {
    //             $data = $response->body();
    //             $data = json_decode($data, true);
    //             $generationId = $data['sdGenerationJob']['generationId'];
    //             while (true) {
    //                 $response = Http::withHeaders([
    //                     'accept' => 'application/json',
    //                     'authorization' => 'Bearer ' . $this->leo_key,
    //                 ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

    //                 if ($response->successful()) {
    //                     $data = $response->json();
    //                     if (!empty($data['generations_by_pk']['generated_images'])) {
    //                         // Get the original image URL and upload it to Cloudflare
    //                         $firstImageUrl = $data['generations_by_pk']['generated_images'][0]['url'];
    //                         $originalImageUrl = $this->uploadToCloudFlareFromCdn(
    //                             $data['generations_by_pk']['generated_images'][0]['url'],
    //                             'image-result' . time(),
    //                             $feature->slug,
    //                             Auth::guard('customer')->id() . '-gen' . $generationId
    //                         );
    //                         // By default, set $image to $originalImageUrl
    //                         $image = $originalImageUrl;
    //                         // Check if background removal is enabled
    //                         if ($feature->remove_bg == 1) {
    //                             $imageWithoutBg = $this->removeBackground($originalImageUrl);
    //                             $image = $this->uploadToCloudFlareFromCdn(
    //                                 $imageWithoutBg,
    //                                 'image-' . time(),
    //                                 $feature->slug,
    //                                 Auth::guard('customer')->id() . 'result-gen' . $generationId
    //                             );
    //                         }
    //                         // Log the activity with the final image URL
    //                         Activities::create([
    //                             'customer_id' => Auth::guard('customer')->id(),
    //                             'photo_id' => $id_img,
    //                             'features_id' => $featuresId,
    //                             'image_result' => $image,
    //                             'image_size' => $result->width,
    //                             'attributes'=>json_encode([
    //                                 'ai_model' => 'Leo AI',
    //                                 'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
    //                                 'modelId' => $result->model_id,
    //                                 'prompt' => $result->prompt,
    //                                 'presetStyle' => $result->presetStyle,
    //                                 'num_images' => 1,
    //                                 'alchemy' => true,
    //                                 'controlnets' => isset($initImageId) && $initImageId !== null ? [
    //                                     [
    //                                         'initImageId' => $initImageId,
    //                                         'initImageType' => 'UPLOADED',
    //                                         'preprocessorId' => (int) $result->preprocessorId,
    //                                         'strengthType' => $result->strengthType,
    //                         'weight' => $result->weight,

    //                                     ]
    //                                 ] : [],
    //                                 'init_image_id' => $image_id,
    //                                 'init_strength' => 0.5,
    //                                     ]),
    //                             'request' => json_encode($request->all()),
    //                             'ai_model' => 'Leo AI',
    //                             'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
    //                         ]);

    //                         // Return the JSON response with both the original and modified URLs
    //                         if ($feature->remove_bg == 1) {
    //                             return response()->json([
    //                                 'status' => true,
    //                                 'url' => $image,              // Final image URL (with or without background removed)
    //                                 'bg_url' => $originalImageUrl  // Original image URL
    //                             ]);
    //                         } else {
    //                             return response()->json([
    //                                 'status' => true,
    //                                 'url' => $image,
    //                             ]);
    //                         }
    //                     }
    //                 }
    //             }
    //         } else {
    //             return response()->json(['status' => 'error', 'message' => 'Failed to upload image.', 'details' => $response->body()]);
    //         }
    //     }
    // }
    /* New code 2024-11-24 */
    public function cartoon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
            'slug' => 'required',
            'id_size' => 'nullable',
            'pro_acc' => 'nullable|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $file = $request->file('image');

        $feature = $this->getFeatureBySlug($request->slug);
        if (!$feature) {
            return response()->json(['status' => 'error', 'message' => 'Feature not found'], 404);
        }

        if ($feature->is_pro == 1 && (!$request->has('pro_acc') || $request->pro_acc == 0)) {
            return response()->json(['status' => false, 'error' => 'Not accepted'], 401);
        }

        if ($request->slug == '2d-ai-cartoon') {
            $file = $request->file('image');

            // Generate a unique filename
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->getPathname();

            // API Token (using Vance key set in the constructor)
            $apiToken = $this->vancekey;
            if (!$apiToken||$apiToken=='0'||$apiToken==0) {
                return response()->json(['error' => 'No valid API key available'], 500);
            }
            $response = Http::attach('file', file_get_contents($filePath), $filename)
                ->post('https://api-service.vanceai.com/web_api/v1/upload', [
                    'api_token' => $apiToken,
                ]);
            if ($response->successful()) {
                // Retrieve the 'uid' from the response
                $data = $response->json();
                $uid = $data['data']['uid'];

                // Step 2: Transform the uploaded image
                $transformResponse = Http::post('https://api-service.vanceai.com/web_api/v1/transform', [
                    'api_token' => $apiToken,
                    'uid' => $uid,
                    'jconfig' => json_encode([
                        'job' => 'animegan',
                        'config' => [
                            'module' => 'animegan2',
                            'module_params' => [
                                'model_name' => 'Animegan2Stable',
                                'single_face' => true,
                                'denoising_strength' => 0.75,
                            ]
                        ]
                    ]),
                ]);

                if ($transformResponse->successful()) {
                    // Retrieve the 'trans_id' from the transform response
                    $transformData = $transformResponse->json();
                    $transId = $transformData['data']['trans_id'];
                    $response = Http::post('https://api-service.vanceai.com/web_api/v1/download', [
                        'api_token' => $apiToken,
                        'trans_id' => $transId,
                    ]);

                    if ($response->successful()) {
                        $fileContent = $response->body(); // Get the image data
                        $folder = 'uploadcartoon';
                        $filename = 'transformed_' . uniqid(); // Generate a unique filename

                        // Step 1: Save the binary content temporarily as a file
                        $tempFilePath = storage_path('app/temp_transformed_image.jpg');
                        file_put_contents($tempFilePath, $fileContent);
                        $tempFile = new \Illuminate\Http\File($tempFilePath);

                        // Step 2: Upload the temporary file to Cloudflare R2
                        $cloudflareLink = $this->uploadToCloudFlareFromFile($tempFile, $folder, $filename);
                        unlink($tempFilePath);
                        if($feature->remove_bg==1){
                           $cloudflareLink1= $this->removeBackground($cloudflareLink);

                        }
                        if (str_starts_with($cloudflareLink, 'error')) {
                            return response()->json([
                                'status' => 'error',
                                'message' => $cloudflareLink,
                            ], 500);
                        }

                        return response()->json([
                            'status'=>'success',
                            'bg_url'=>$cloudflareLink,
                            'url' => $cloudflareLink1,
                        ]);
                    }
                }
            }
        }
        list($originalWidth, $originalHeight) = getimagesize($file->getRealPath());
        $width = round($originalWidth / 8) * 8;
        $width = min(1536, $width);
        $height = round($originalHeight / 8) * 8;
        $result = $this->uploadImage($file);
        $image_id = $result['id'];
        $routePath = $request->path();
        $is_feature=true;
        $result = Features::where('slug', $request->slug)->first();
        $feature = Features::where('slug', $request->slug)->first();
        if (!$result) {
            $result = SubFeatures::where('slug', $request->slug)->first();
            $feature = SubFeatures::where('slug', $request->slug)->first();
            $is_feature=false;
        }
        if ($result->is_pro == 1 && $request->pro_acc == 0) {
            return response()->json(['status' => false, 'error' => 'Not accepted'], 401);
        }
        $initImageId = $result->initImageId;
        if ($request->has('id_size')) {
            $id_feature=0;
            if($is_feature==false){
                $id_feature=$feature->feature_id;
            }else{
                $id_feature=$feature->id;
            }
            $check = FeaturesSizes::where([
                'feature_id' => $id_feature,
                'size_id' => $request->id_size
            ])->first();
            if (!$check) {
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
                    "width"=> 1024,
                    'alchemy' => true,
                    isset($initImageId) && $initImageId !== null ? [
                        'controlnets' => [
                            [
                                'initImageId' => $initImageId,
                                'initImageType' => 'UPLOADED',
                                'preprocessorId' => (int) $result->preprocessorId,
                                'strengthType' => $result->strengthType,
                                'weight' => $result->weight,
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
                            'authorization' => 'Bearer ' . $this->leo_key,
                        ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

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
                                    'attributes'=>json_encode([
                                        'ai_model' => 'Leo AI',
                                        'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                                        'modelId' => $result->model_id,
                                        'prompt' => $result->prompt,
                                        'presetStyle' => $result->presetStyle,
                                        'num_images' => 1,
                                        'alchemy' => true,
                                        'controlnets' => isset($initImageId) && $initImageId !== null ? [
                                            [
                                                'initImageId' => $initImageId,
                                                'initImageType' => 'UPLOADED',
                                                'preprocessorId' => (int) $result->preprocessorId,
                                                'strengthType' => $result->strengthType,
                                'weight' => $result->weight,
                                            ]
                                        ] : [],
                                        'init_image_id' => $image_id,
                                        'init_strength' => 0.5,
                                            ]),
                                        'request' => json_encode($request->all()),
                                    'ai_model' => 'Leo AI',
                                    'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                                ]);

                                // Return the JSON response with both the original and modified URLs
                                if ($feature->remove_bg == 1) {
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,              // Final image URL (with or without background removed)
                                        'bg_url' => $originalImageUrl  // Original image URL
                                    ]);
                                } else {
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
            } else {
                $size = ImageSize::where('id', $request->id_size)->first();
                $height = $size->height;
                $width = $size->width;
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
                                'strengthType' => $result->strengthType,
                                'weight' => $result->weight,
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
                            'authorization' => 'Bearer ' . $this->leo_key,
                        ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

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
                                    'attributes'=>json_encode([
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
                                                    'strengthType' => $result->strengthType,
                                                    'weight' => $result->weight,
                                                ]
                                            ]
                                        ] : [],
                                        "init_image_id" => $image_id,
                                        "init_strength" => 0.5,
                                    ]),
                                    'request' => json_encode($request->all()),
                                    'ai_model' => 'Leo AI',
                                    'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                                ]);
                                // Return the JSON response with both the original and modified URLs
                                if ($feature->remove_bg == 1) {
                                    return response()->json([
                                        'status' => true,
                                        'url' => $image,              // Final image URL (with or without background removed)
                                        'bg_url' => $originalImageUrl  // Original image URL
                                    ]);
                                } else {
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
        } else {
            $initImageId = $result->initImageId;
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
                'height'=>$height,
                'width'=>$width,
                isset($initImageId) && $initImageId !== null ? [
                    'controlnets' => [
                        [
                            'initImageId' => $initImageId,
                            'initImageType' => 'UPLOADED',
                            'preprocessorId' => (int) $result->preprocessorId,
                            'strengthType' => $result->strengthType,
                            'weight' => $result->weight,
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
                        'authorization' => 'Bearer ' . $this->leo_key,
                    ])->get('https://cloud.leonardo.ai/api/rest/v1/generations/' . $generationId);

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
                                'attributes'=>json_encode([
                                    'ai_model' => 'Leo AI',
                                    'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                                    'modelId' => $result->model_id,
                                    'prompt' => $result->prompt,
                                    'presetStyle' => $result->presetStyle,
                                    'num_images' => 1,
                                    'alchemy' => true,
                                    'controlnets' => isset($initImageId) && $initImageId !== null ? [
                                        [
                                            'initImageId' => $initImageId,
                                            'initImageType' => 'UPLOADED',
                                            'preprocessorId' => (int) $result->preprocessorId,
                                            'strengthType' => $result->strengthType,
                            'weight' => $result->weight,

                                        ]
                                    ] : [],
                                    'init_image_id' => $image_id,
                                    'init_strength' => 0.5,
                                        ]),
                                'request' => json_encode($request->all()),
                                'ai_model' => 'Leo AI',
                                'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations/',
                            ]);
                            if ($feature->remove_bg == 1) {
                                return response()->json([
                                    'status' => true,
                                    'url' => $image,
                                    'bg_url' => $originalImageUrl
                                ]);
                            } else {
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
    private function uploadToCloudFlareFromFile1($imageFile, $folder, $filename)
    {
        try {
            // Step 1: Check if the file exists
            if (!file_exists($imageFile)) {
                Log::error('File does not exist: ' . $imageFile);
                return 'error: file does not exist';
            }
            $filename = str_replace(' ', '', $filename);

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
            $r2object = $folder . '/' . $filename;

            // Step 4: Upload the file to Cloudflare R2
            try {
                $result = $s3Client->putObject([
                    'Bucket' => $r2bucket,
                    'Key' => $r2object,
                    'Body' => fopen($imageFile, 'rb'), // Open the file as a binary stream
                    'ContentType' => 'image/png',
                ]);

                // Generate the CDN URL using the custom domain
                $cdnUrl = "https://$accountid.r2.cloudflarestorage.com/$r2bucket/$r2object";
                return $cdnUrl;
            } catch (S3Exception $e) {
                return 'error: ' . $e->getMessage();
            }
        } catch (\Exception $e) {
            Log::error('Error uploading image to Cloudflare R2: ' . $e->getMessage());
            return 'error: ' . $e->getMessage();
        }
    }
    /**
     * Get Feature or SubFeature by slug
     */
    private function getFeatureBySlug($slug)
    {
        $feature = Features::where('slug', $slug)->first();
        return $feature ?: SubFeatures::where('slug', $slug)->first();
    }

    /**
     * Check if the feature size exists
     */
    private function checkFeatureSize($feature, $sizeId, $isFeature)
    {
        $featureId = $isFeature ? $feature->id : $feature->feature_id;
        return FeaturesSizes::where([
            'feature_id' => $featureId,
            'size_id' => $sizeId
        ])->exists();
    }

    /**
     * Process image generation
     */
    private function processImageGeneration($feature, $file, $image_id, $initImageId, $request, $featuresId)
    {
        $response = $this->sendImageGenerationRequest($feature, $image_id, $initImageId);
        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to generate image.', 'details' => $response->body()]);
        }

        return $this->handleImageGenerationResponse($response, $feature, $file, $featuresId, $request);
    }

    /**
     * Generate custom-sized image
     */
    private function generateCustomSizeImage($feature, $file, $image_id, $initImageId, $request)
    {
        $size = ImageSize::find($request->id_size);
        if (!$size) {
            return response()->json(['status' => 'error', 'message' => 'Invalid size ID'], 400);
        }

        $response = $this->sendImageGenerationRequest($feature, $image_id, $initImageId, [
            'width' => $size->width,
            'height' => $size->height
        ]);

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to generate image.', 'details' => $response->body()]);
        }

        return $this->handleImageGenerationResponse($response, $feature, $file, $feature->id, $request);
    }

    /**
     * Send image generation request
     */
    private function sendImageGenerationRequest($feature, $image_id, $initImageId, $customParams = [])
    {
        if (!$feature->weight) {
            $params = array_merge([
                'modelId' => $feature->model_id,
                'prompt' => $feature->prompt,
                'presetStyle' => $feature->presetStyle,
                'num_images' => 1,
                'alchemy' => true,
                'init_image_id' => $image_id,
                'init_strength' => 0.5,
                'controlnets' => isset($initImageId) ? [[
                    'initImageId' => $initImageId,
                    'initImageType' => 'UPLOADED',
                    'preprocessorId' => (int) $feature->preprocessorId,
                    'strengthType' => $feature->strengthType,
                ]] : []
            ], $customParams);
        } else {
            $params = array_merge([
                'modelId' => $feature->model_id,
                'prompt' => $feature->prompt,
                'presetStyle' => $feature->presetStyle,
                'num_images' => 1,
                'alchemy' => true,
                'init_image_id' => $image_id,
                'init_strength' => 0.5,
                'controlnets' => isset($initImageId) ? [[
                    'initImageId' => $initImageId,
                    'initImageType' => 'UPLOADED',
                    'preprocessorId' => (int) $feature->preprocessorId,
                    'strengthType' => $feature->strengthType,
                    'weight' => $feature->weight,
                ]] : []
            ], $customParams);
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->leo_key,
            'Accept' => 'application/json',
        ])->post('https://cloud.leonardo.ai/api/rest/v1/generations', $params);
    }

    /**
     * Handle image generation response
     */
    private function handleImageGenerationResponse($response, $feature, $file, $featuresId, $request)
    {
        $data = json_decode($response->body(), true);
        $generationId = $data['sdGenerationJob']['generationId'];

        // Polling logic
        $imageData = $this->pollGenerationResult($generationId);
        if (!$imageData) {
            return response()->json(['status' => 'error', 'message' => 'Image generation timed out'], 500);
        }

        $originalImageUrl = $this->uploadToCloudFlareFromCdn($imageData[0]['url'], 'image-result' . time(), $feature->slug);
        $image = $this->processGeneratedImage($imageData, $feature);
        $this->logActivity($image, $file, $featuresId, $request, $feature);

        if ($feature->remove_bg) {
            return response()->json([
                'status' => true,
                'url' => $image,              // Final image URL (with or without background removed)
                'bg_url' => $originalImageUrl  // Original image URL
            ]);
        }

        return response()->json(['status' => true, 'url' => $image]);
    }

    /**
     * Poll generation result
     */
    private function pollGenerationResult($generationId)
    {
        while (true) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->leo_key,
                'Accept' => 'application/json',
            ])->get("https://cloud.leonardo.ai/api/rest/v1/generations/{$generationId}");

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();
            if (!empty($data['generations_by_pk']['generated_images'])) {
                return $data['generations_by_pk']['generated_images'];
            }

            sleep(1);
        }
    }

    /**
     * Process generated image
     */
    private function processGeneratedImage($imageData, $feature)
    {
        $originalImageUrl = $this->uploadToCloudFlareFromCdn($imageData[0]['url'], 'image-result' . time(), $feature->slug);
        if ($feature->remove_bg) {
            $imageWithoutBg = $this->removeBackground($originalImageUrl);
            return $this->uploadToCloudFlareFromCdn($imageWithoutBg, 'image-bg-removed' . time(), $feature->slug);
        }
        return $originalImageUrl;
    }

    /**
     * Log activity
     */
    private function logActivity($image, $file, $featuresId, $request, $feature)
    {
        Activities::create([
            'customer_id' => Auth::guard('customer')->id(),
            'photo_id' => $this->uploadServerImage($file),
            'features_id' => $featuresId,
            'image_result' => $image,
            'ai_model' => 'Leo AI',
            'api_endpoint' => 'https://cloud.leonardo.ai/api/rest/v1/generations',
            'attributes' => json_encode([
                'modelId' => $feature->model_id,
                'prompt' => $feature->prompt,
                'presetStyle' => $feature->presetStyle,
                'num_images' => 1,
                'alchemy' => true,
                'init_strength' => 0.5,
                'controlnets' => isset($feature->initImageId) ? [[
                    'initImageId' => $feature->initImageId,
                    'initImageType' => 'UPLOADED',
                    'preprocessorId' => (int) $feature->preprocessorId,
                    'strengthType' => $feature->strengthType,
                ]] : []
            ]),
            'request' => json_encode($request->all())
        ]);
    }

    /**
     * Transform cartoon style via Vance
     */
    public function transformViaVance($request, $file)
    {
        $filename = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->getPathname();

        // Upload the image
        $uploadResponse = $this->uploadToApi($filePath, $filename);

        if (!$uploadResponse->successful()) {
            return response()->json(['error' => 'Failed to upload image'], 500);
        }
        $uid = $uploadResponse->json()['data']['uid'];
        $newfilename = $uid . '_' . time();

        // Transform the image
        $transformResponse = $this->transformImage($uid);
        // if (!$transformResponse->successful()) {
        //     return response()->json(['error' => 'Failed to transform image'], 500);
        // }

        // $transId = $transformResponse->json()['data']['trans_id'];
        // // Download the transformed image
        // $downloadResponse = Http::post('https://api-service.vanceai.com/web_api/v1/download', [
        //     'api_token' => $this->vancekey,
        //     'trans_id' => $transId,
        // ]);
        // return $downloadResponse;

    }

    /**
     * Upload image to API Vance
     */
    private function uploadToApi($filePath, $filename)
    {
        return Http::attach('file', file_get_contents($filePath), $filename)
            ->post('https://api-service.vanceai.com/web_api/v1/upload', [
                'api_token' => $this->vancekey,
            ]);
    }

    /**
     * Transform the uploaded image via Vance.
     */
    private function transformImage($uid)
    {
        $transformResponse =  Http::post('https://api-service.vanceai.com/web_api/v1/transform', [
            'api_token' => $this->vancekey,
            'uid' => $uid,
            'jconfig' => json_encode([
                'name' => 'animegan',
                'config' => [
                    'module' => 'animegan2',
                    'module_params' => [
                        'model_name' => 'Animegan2Stable',
                        'single_face' => true,
                        'denoising_strength' => 0.75,
                    ]
                ]
            ]),
        ]);
        if ($transformResponse->successful()) {
            // Get the response data from the transform API
            $transformData = $transformResponse->json();
            // dd($transformData)
            $transId = $transformData['data']['trans_id'];
            sleep(3);
            // Step 3: Request to download the transformed image using trans_id
            $downloadResponse = Http::post('https://api-service.vanceai.com/web_api/v1/download', [
                'api_token' => $this->vancekey,
                'trans_id' => $transId,
            ]);
            return $downloadResponse;
            // Check if the download request was successful
            if ($downloadResponse->successful()) {
                $fileContent = $downloadResponse;
                // Temporary local storage path for the image
                $filename = time() . '.jpg'; // Dynamic filename based on the current timestamp
                $storagePath = 'transformed_images/' . $filename;
                return $storagePath;
                // Save the file temporarily to the local disk
                Storage::disk('public')->put($storagePath, $fileContent);

                // Define the folder in Cloudflare where the file will be uploaded
                $folder = 'uploadcartoon';

                // Full path of the local file to pass to Cloudflare upload
                $localFilePath = Storage::disk('public')->path($storagePath);

                // Upload the file to Cloudflare
                $cloudflareLink = $this->uploadToCloudFlareFromFile($localFilePath, $folder, $filename);

                // Delete the temporary local file
                Storage::disk('public')->delete($storagePath);
                return $cloudflareLink;
            } else {
                // Handle error if transform API request fails
                return response()->json(['error' => 'Failed to transform image'], 500);
            }
        }
    }

    /**
     * Download the transformed image from Vance.
     */
    private function downloadTransformedImage($transId)
    {
        return Http::post('https://api-service.vanceai.com/web_api/v1/download', [
            'api_token' => $this->vancekey,
            'trans_id' => $transId,
        ]);
    }

    /**
     * Store and upload image to Cloudflare.
     */
    private function storeAndUploadToCloudflare($fileContent, $filename, $extension)
    {
        // Xác định thư mục public tùy chỉnh
        $customPublicPath = public_path(); // Đây là đường dẫn đến thư mục public mặc định
        $filePath = $customPublicPath . '/transformed_images/' . $filename . '.' . $extension;

        // Lưu file vào thư mục public
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        file_put_contents($filePath, $fileContent);
        $public_link = url('/') . "/public/transformed_images/" . $filename . '.' . $extension;
        Log::debug($public_link);

        // Định nghĩa thư mục upload trên Cloudflare
        $folder = 'uploadcartoon';

        // Upload file lên Cloudflare
        try {
            $cloudflareLink = $this->uploadToCloudFlareFromUrl($public_link, $folder, $filename);
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
            $cloudflareLink = '';
        }

        // Trả về link từ Cloudflare
        return $cloudflareLink;
    }

    /* End new code 2024-11-24 */

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
