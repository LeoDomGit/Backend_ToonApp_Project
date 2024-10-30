<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
class ImageAIController extends Controller
{
    protected $key =env(IMAGE_API_KEY);
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

    private function uploadToCloudFlare($folder, $filename, $imageresponse, $code_profile)
    {
        try {
            // Decode base64 image
            $imageData = base64_decode($imageresponse);
            $r2object = $folder.'/'.$filename.'.jpg'; // You can generate a unique name for each image
  
            $accountid = '453d5dc9390394015b582d09c1e82365';
            $r2bucket = 'imagehub';
  
            // Example usage
            $accessKey = '246eacf6e9a33cfe39dd02095820634d';
            $secretKey = 'a98e160d60ecb864e5098f9ba380e347b2e4124f271add8c3d84b9e859c4de98';
            $region = 'auto';
            $contentType = 'image/jpeg'; // Content type of your file
  
            // AWS credentials and Cloudflare R2 endpoint
            $credentials = [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ];
            $endpoint = "https://$accountid.r2.cloudflarestorage.com";
  
            // Create an S3 client
            $s3Client = new S3Client([
                'version'     => 'latest',
                'region'      => $region, // Change this to your region
                'credentials' => $credentials,
                'endpoint'    => $endpoint,
            ]);
  
            // Upload file to Cloudflare R2
            try {
                $result = $s3Client->putObject([
                    'Bucket' => $r2bucket,
                    'Key'    => $r2object,
                    'Body'   => $imageData,
                    'ContentType' => $contentType,
                ]);
  
                $this->storeRequest(2, $code_profile." - Upload to CloudFlare", "CloudFlare", 1, $endpoint, $r2object, $result, 0);
  
                return $r2object;
            } catch (S3Exception $e) {
                Log::debug("Error uploading file: ". $e->getMessage());
            }
        } catch (\Throwable $th) {
            Log::debug($th);
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
            return response()->json(['check' => true, 'data' => $data]);
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
