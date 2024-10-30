<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
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

    private function storeRequest($request_type, $prompt, $modelai, $method, $url_endpoint, $postfields, $response, $id_content_category) {
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
            return 'error'.$e->getMessage();
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
            $image_url=$data['data']['url'];
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $folder = 'RemoveBackground';      
            $code_profile = 'image-' . time(); 
            $cdnUrl = $this->uploadToCloudFlareFromFile($image_url, $code_profile, $folder, $filename);
            return response()->json(['check' => true, 'url' => $cdnUrl,'data'=>$data]);
        } else {
            return response()->json(['check' => false, 'msg' => 'Failed to process image', 'error' => $response->body()]);
        }
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
