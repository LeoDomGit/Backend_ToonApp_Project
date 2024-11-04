<?php

namespace App\Http\Controllers;

use App\Models\Activities;
use App\Models\Features;
use App\Models\Photos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class ImageAIController extends Controller
{
    protected $key;
    protected $client;
    protected $aws_secret_key;
    protected $aws_access_key;


    /**
     * Display a listing of the resource.
     */

    public function __construct()
    {
        $this->key = env('IMAGE_API_KEY');
        $this->aws_secret_key = 'b52dcdbea046cc2cc13a5b767a1c71ea8acbe96422b3e45525d3678ce2b5ed3e';
        $this->aws_access_key = 'cbb3e2fea7c7f3e7af09b67eeec7d62c';
        $this->client = new Client();
    }
    private function uploadServerImage($image)
    {
        $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $folder = 'users-' . Auth::guard('customer')->id() . '/';
        $code_profile = 'image-' . time();

        // Step 1: Upload original image to CDN
        $originalImageUrl = $this->uploadToCloudFlareFromFile(
            $image->getRealPath(), // Use the real path of the uploaded file
            $code_profile,
            $folder,
            $filename
        );
        $id = Photos::insertGetId([
            'customer_id' => Auth::guard('customer')->id(),
            'original_image_path' => $originalImageUrl,
        ]);

        return $id;
    }

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
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $client = $this->client;

        if ($request->hasFile('image') && $request->hasFile('background')) {
            $image = $request->file('image');
            $id_img = $this->uploadServerImage($image);
            $background = $request->file('background');
            $response = $client->request('POST', 'https://api.picsart.io/tools/1.0/removebg', [
                'multipart' => [
                    [
                        'name' => 'output_type',
                        'contents' => 'cutout'
                    ],
                    [
                        'name' => 'bg_blur',
                        'contents' => '0'
                    ],
                    [
                        'name' => 'scale',
                        'contents' => 'fit'
                    ],
                    [
                        'name' => 'auto_center',
                        'contents' => 'false'
                    ],
                    [
                        'name' => 'stroke_size',
                        'contents' => '0'
                    ],
                    [
                        'name' => 'stroke_color',
                        'contents' => 'FFFFFF'
                    ],
                    [
                        'name' => 'stroke_opacity',
                        'contents' => '100'
                    ],
                    [
                        'name' => 'shadow',
                        'contents' => 'disabled'
                    ],
                    [
                        'name' => 'shadow_opacity',
                        'contents' => '20'
                    ],
                    [
                        'name' => 'shadow_blur',
                        'contents' => '50'
                    ],
                    [
                        'name' => 'format',
                        'contents' => 'PNG'
                    ],
                    [
                        'name' => 'image',
                        'filename' => $image->getClientOriginalName(),
                        'contents' => fopen($image->getPathname(), 'r'),
                        'headers' => [
                            'Content-Type' => 'image'
                        ]
                    ],
                    [
                        'name' => 'bg_image',
                        'filename' => $background->getClientOriginalName(),
                        'contents' => fopen($background->getPathname(), 'r'),
                        'headers' => [
                            'Content-Type' => 'image'
                        ]
                    ]
                ],
                'headers' => [
                    'X-Picsart-API-Key' => $this->key,
                    'accept' => 'application/json',
                ],
            ]);

            // Fetch response body
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                $image_url = $data['data']['url'];
                $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $folder = 'RemoveBackground';
                $code_profile = 'image-' . time();
                $cdnUrl = $this->uploadToCloudFlareFromFile($image_url, $code_profile, $folder, $filename);
                $this->createActivities($id_img, $cdnUrl, $image->getSize(), '/api/remove_background', 'https://api.picsart.io/tools/1.0/removebg');
              activity('remove_background')
                ->withProperties([
                    'cdnUrl' => $cdnUrl,
                    'size' => $image->getSize(),
                ])
                ->log('Image processed successfully');
                return response()->json(['check' => true, 'url' => $cdnUrl,'id_img'=>$id_img]);
            } else {
                return response()->json(['check' => false, 'msg' => 'Failed to process image', 'error' => $response->getBody()->getContents()]);
            }
        } else {
            $image = $request->file('image');
            $id_img = $this->uploadServerImage($image);
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

            // Check response status
            if ($response->successful()) {
                $data = $response->json();
                $image_url = $data['data']['url'];
                $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $folder = 'RemoveBackground';
                $code_profile = 'image-' . time();
                $cdnUrl = $this->uploadToCloudFlareFromFile($image_url, $code_profile, $folder, $filename);
                $this->createActivities($id_img, $cdnUrl, $image->getSize(), '/api/remove_background', 'https://api.picsart.io/tools/1.0/removebg');
              activity('remove_background')
                ->withProperties([
                    'cdnUrl' => $cdnUrl,
                    'size' => $image->getSize(),
                ])
                ->log('Image processed successfully');
                return response()->json(['check' => true, 'url' => $cdnUrl,'id_img'=>$id_img]);
            } else {
                return response()->json(['check' => false, 'msg' => 'Failed to process image', 'error' => $response->body()]);
            }
        }
    }


    public function cartoonStyle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
            'reference_image' => 'required|mimes:png,jpg,jpeg',
            'level' => 'in:l1,l2,l3,l4,l5',
        ]);
        
        if ($validator->fails()) {
            activity('claymation')
                ->withProperties(['error' => $validator->errors()->first()])
                ->log('Validation failed');
        
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        
        $image = $request->file('image');
        $id_img = $this->uploadServerImage($image);
        $referenceImage = $request->file('reference_image');
        $level = $request->input('level', 'l5'); // Default to l5
        
        // Log the start of the background removal process
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
                return response()->json(['check' => true, 'url' => $cdnUrl]);
            } else {
                activity('claymation')
                    ->withProperties(['error' => $response->body()])
                    ->log('Failed to process image');
        
                return response()->json(['check' => false, 'msg' => 'Failed to process image', 'error' => $response->body()]);
            }
        } else {
            activity('removeBackground')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to remove background');
        
            return response()->json(['check' => false, 'msg' => 'Failed to remove background', 'error' => $response->body()]);
        }
        
    }
    private function storeRequest($request_type, $prompt, $modelai, $method, $url_endpoint, $postfields, $response, $id_content_category)
    {
        $request = new RequestModel();
        $request->id_user = Auth::user()->id;
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
    private function uploadToCloudFlareFromFile($image_url, $code_profile, $folder, $filename)
    {
        try {
            // Step 1: Download the image
            $ch = curl_init($image_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);  // Timeout in seconds
            $imageData = curl_exec($ch);

            if ($imageData === false) {
                // Handle download error
                Log::error('Curl error: ' . curl_error($ch));
                return 'error';
            }

            curl_close($ch);

            // Step 2: Save the downloaded image temporarily
            $localPath = 'local-image.jpg';
            file_put_contents($localPath, $imageData);

            // Step 3: Prepare Cloudflare R2 credentials and settings
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

            // Step 4: Define the object path and name in R2
            $r2object = $folder . '/' . $filename . '.jpg';

            // Step 5: Upload the file to Cloudflare R2
            try {
                $result = $s3Client->putObject([
                    'Bucket' => $r2bucket,
                    'Key' => $r2object,
                    'Body' => file_get_contents($localPath),
                    'ContentType' => 'image/jpeg',
                ]);

                // Generate the CDN URL using the custom domain
                $cdnUrl = "https://artapp.promptme.info/$folder/$filename.jpg";
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

    public function removeBackground(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
        ]);

        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }

        $image = $request->file('image');
        $id_img = $this->uploadServerImage($image);

        // Send request to Picsart API
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

        // Check response status
        if ($response->successful()) {
            $data = $response->json();
            $image_url = $data['data']['url'];
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $folder = 'RemoveBackground';
            $code_profile = 'image-' . time();
            $cdnUrl = $this->uploadToCloudFlareFromFile($image_url, $code_profile, $folder, $filename);
            activity('removeBackground')
                ->withProperties([
                    'id_img' => $id_img,
                    'cdnUrl' => $cdnUrl,
                    'image_size' => $image->getSize(),
                    'api_url' => 'https://api.picsart.io/tools/1.0/removebg'
                ])
                ->log('Successfully removed background from image');
            $this->createActivities($id_img, $cdnUrl, $image->getSize(), '/api/remove_background', 'https://api.picsart.io/tools/1.0/removebg');
            return response()->json(['check' => true, 'url' => $cdnUrl]);
        } else {
            // Log activity on failure
            activity('removeBackground')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to remove background from image');

            return response()->json(['check' => false, 'msg' => 'Failed to process image', 'error' => $response->body()]);
        }
    }

    public function animalToon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
        ]);

        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
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
            return response()->json(['check' => true, 'url' => $cdnUrl, ]);
        } else {
            // Log activity on failure
            activity('animalToon')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to process image for animal toon effect');

            return response()->json(['check' => false, 'msg' => 'Failed to process image', 'error' => $response->body()]);
        }
    }

    public function createActivities($photoId, $imageResult, $imageSize, $featuresId, $apiEndpoint, $aiModel = null)
    {
        // Set default AI model to 'Picsart' if not provided
        $aiModel = $aiModel ?? 'Picsart';
        $result = Features::where('api_endpoint','like','%'.$featuresId.'%')->first();
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
    public function test($photoId, $imageResult, $imageSize, $featuresId, $apiEndpoint, $aiModel = null)
    {
        // Set default AI model to 'Picsart' if not provided
        // $aiModel = $aiModel ?? 'Picsart';
        $result = Features::where('name', 'like', '%' . $featuresId . '%')->first();
        return response()->json($result);
        // $featuresId = $result->id;
        // return Activities::create([
        //     'customer_id' => Auth::guard('customer')->id(),
        //     'photo_id' => $photoId,
        //     'features_id' => $featuresId,
        //     'image_result' => $imageResult,
        //     'image_size' => $imageSize,
        //     'ai_model' => $aiModel,
        //     'api_endpoint' => $apiEndpoint,
        // ]);
    }
    public function claymation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
            'reference_image' => 'required|mimes:png,jpg,jpeg',
            'level' => 'in:l1,l2,l3,l4,l5',
        ]);

        if ($validator->fails()) {
            activity('claymation')
                ->withProperties(['error' => $validator->errors()->first()])
                ->log('Validation failed');

            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }

        $image = $request->file('image');
        $id_img = $this->uploadServerImage($image);
        $referenceImage = $request->file('reference_image');
        $level = $request->input('level', 'l5'); // Default to l5

        // Log the start of the API request
        activity('claymation')
            ->withProperties([
                'id_img' => $id_img,
                'level' => $level,
            ])
            ->log('Sending request to Picsart API');

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

        // Check response status and log the result
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
            return response()->json(['check' => true, 'url' => $cdnUrl]);
        } else {
            activity('claymation')
                ->withProperties([
                    'error' => $response->body()
                ])
                ->log('Failed to process image');

            return response()->json(['check' => false, 'msg' => 'Failed to process image', 'error' => $response->body()]);
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
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
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
            return response()->json(['check' => true, 'url' => $cdnUrl]);
        } else {
            // Log failed activity attempt
            activity('disneyToon')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to process image for Disney-style transformation');

            return response()->json(['check' => false, 'msg' => 'Failed to process image', 'error' => $response->body()]);
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
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
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
            return response()->json(['check' => true, 'url' => $cdnUrl]);
        } else {
            // Log activity on failure
            activity('disneyCharators')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to process image for Disney characters transformation');

            return response()->json(['check' => false, 'msg' => 'Failed to process image', 'error' => $response->body()]);
        }
    }


    public function fullBodyCartoon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,jpeg',
        ]);

        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
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
            return response()->json(['check' => true, 'url' => $cdnUrl ]);
        } else {
            // Log activity on failure
            activity('fullBodyCartoon')
                ->withProperties(['error' => $response->body()])
                ->log('Failed to process image for full-body cartoon effect');
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
