<?php
// app/Http/Controllers/ImageController.php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class ImageController extends Controller
{
    private $aws_secret_key;
    private $aws_access_key;

    private $key;
    private $client;

    public function __construct(Request $request)
    {
        $this->aws_secret_key = 'b52dcdbea046cc2cc13a5b767a1c71ea8acbe96422b3e45525d3678ce2b5ed3e';
        $this->aws_access_key = 'cbb3e2fea7c7f3e7af09b67eeec7d62c';
        $this->key = '432990d8f58633e8b2d7503228a4c2b2';
        $this->client = new Client();
    }

    public function index()
    {
        $images = Image::all();

        return Inertia::render('Images/Index', [
            'data' => $images,
            'message' => $images->isEmpty() ? 'No images found.' : null,
        ]);
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
    // Upload ảnh
    public function uploadImage(Request $request)
    {
        // Validate if the file is uploaded and is valid
        if (!$request->hasFile('image') || !$request->file('image')->isValid()) {
            return response()->json(['error' => 'No valid file uploaded'], 400);
        }

        // Get the uploaded file
        $file = $request->file('image');

        // Get the file path and original filename
        $filename = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->getPathname();

        // API Token (assuming it's set on $this->key)
        $apiToken = $this->key;

        // Make the request to the API to upload the image
        $response = Http::attach('file', file_get_contents($filePath), $filename)
            ->post('https://api-service.vanceai.com/web_api/v1/upload', [
                'api_token' => $apiToken,
            ]);

        // Check if the request was successful
        if ($response->successful()) {
            // Get the response data
            $data = $response->json();

            // Retrieve the 'uid' from the response data
            $uid = $data['data']['uid'];
            $transformResponse = Http::post('https://api-service.vanceai.com/web_api/v1/transform', [
                'api_token' => $this->key,  // Use your token here
                'uid' => $uid,
                'jconfig' => json_encode([
                    'name' => 'img2anime',
                    'config' => [
                        'module' => 'img2anime',
                        'module_params' => [
                            'model_name' => 'style4',
                            'prompt' => '',
                            'overwrite' => false,
                            'denoising_strength' => 0.75
                        ]
                    ]
                ])
            ]);

            // Check if the transform request was successful
            if ($transformResponse->successful()) {
                // Get the response data from the transform API
                $transformData = $transformResponse->json();
                // dd($transformData)
                $transId = $transformData['data']['trans_id'];

                // Step 3: Request to download the transformed image using trans_id
                $downloadResponse = Http::post('https://api-service.vanceai.com/web_api/v1/download', [
                    'api_token' => $this->key,
                    'trans_id' => $transId,
                ]);

                // Check if the download request was successful
                if ($downloadResponse->successful()) {
                    // Get the file content from the download response
                    $fileContent = $downloadResponse->body();
                    $storagePath = 'transformed_images/' . time() . '.jpg';  // Change extension if needed
                    Storage::disk('public')->put($storagePath, $fileContent);
                    $storageLink = Storage::url($storagePath);
                    $folder = 'uploadcartoon';
                    $cloudflareLink = $this->uploadToCloudFlareFromFile($file, $folder, $filename);
                    Storage::delete($storagePath);
                    return response()->json([
                        'message' => 'Image uploaded, transformed, and uploaded to Cloudflare successfully',
                        'uid' => $uid,
                        'trans_id' => $transId,
                        'download_link' => $storageLink,
                        'cloudflare_link' => $cloudflareLink, // Add the Cloudflare link to the response
                    ]);
                } else {
                    // Handle error if transform API request fails
                    return response()->json(['error' => 'Failed to transform image'], 500);
                }
                // Return a success message with the UID
                // return response()->json([
                //     'message' => 'Image uploaded successfully',
                //     'uid' => $uid,
                //     'name' => $data['data']['name'],
                //     'thumbnail' => $data['data']['thumbnail'],
                //     'w' => $data['data']['w'],
                //     'h' => $data['data']['h'],
                //     'filesize' => $data['data']['filesize'],
                // ]);
            } else {
                // Return an error if the API request failed
                return response()->json(['error' => 'Failed to upload image'], 500);
            }
        }
    }

    public function download()
    {
        $apiToken = 'fe319f7279703eac15091bb4a2b14054';
        $transId = 'bf24d5279dffd9cf8adee58d66440ea0';

        // Make the API request to download the image
        $response = Http::get('https://api-service.vanceai.com/web_api/v1/download', [
            'api_token' => $apiToken,
            'trans_id' => $transId
        ]);

        // Check if the response is successful
        if ($response->successful()) {
            // Get the content of the image (binary data)
            $imageContent = $response->body();

            // Define a file name (can customize this to include the transaction ID or timestamp)
            $fileName = 'downloaded_image_' . $transId . '.jpg';

            // Store the image in the storage folder
            Storage::put('public/images/' . $fileName, $imageContent);


            // Return a success message or response
            return response()->json(['message' => 'Image downloaded successfully', 'file' => $fileName]);
        } else {
            // Handle errors (e.g., failed download)
            return response()->json(['error' => 'Failed to download image'], 500);
        }
    }


    // Cloudflare upload function
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
                    'ContentType' => 'image/jpeg',
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

    public function transformImage(Request $request)
    {
        // Lấy trans_id và api_token từ request
        $transId = $request->input('trans_id');
        $apiToken = $request->input('api_token');

        // Kiểm tra sự tồn tại của trans_id và api_token
        if (!$transId || !$apiToken) {
            return response()->json(['error' => 'Missing trans_id or api_token'], 400);
        }

        // Lấy thông tin từ bảng TransactionLog dựa trên trans_id
        $transaction = TransactionLog::where('trans_id', $transId)->first();

        // Kiểm tra nếu không tìm thấy giao dịch trong bảng TransactionLog
        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        // Kiểm tra nếu api_token không khớp
        if ($transaction->api_token !== $apiToken) {
            return response()->json(['error' => 'Invalid api_token'], 401);
        }

        // Lấy uid từ bảng TransactionLog
        $transactionUid = $transaction->uid;

        // Lấy thông tin hình ảnh từ bảng Image dựa trên uid
        $image = Image::where('uid', $transactionUid)->first();

        // Kiểm tra nếu không tìm thấy ảnh
        if (!$image) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        // Kiểm tra nếu tệp hình ảnh không tồn tại
        if (!file_exists($image->file_path)) {
            return response()->json(['error' => 'Image file not found at path: ' . $image->file_path], 404);
        }

        // Lấy dữ liệu hình ảnh từ file path và chuyển sang base64
        $imageContent = file_get_contents($image->file_path);
        $base64Image = base64_encode($imageContent);

        // Lưu hình ảnh đã được transform vào file trên máy chủ
        $path = storage_path('app/public/images/') . $transId . '_transformed.jpg';

        // Save the base64 content as an image file
        file_put_contents($path, base64_decode($base64Image));

        // Lưu thông tin hình ảnh đã transform vào cơ sở dữ liệu
        $transformedImage = Image::create([
            'uid' => Str::uuid()->toString(),
            'file_name' => $transId . '_transformed.jpg', // Tên file của ảnh đã transform
            'file_path' => $path, // Đường dẫn tới file đã lưu
            'api_token' => $apiToken, // api_token đã sử dụng
            'trans_id' => $transId, // Lưu lại trans_id
            'base64' => $base64Image, // Base64 của hình ảnh đã transform
        ]);

        // Trả về thông tin base64 và file path của hình ảnh đã transform
        return response()->json([
            'base64' => $base64Image,
            'file_path' => $path, // Đường dẫn tới file đã lưu trên máy chủ
        ]);
    }
}
