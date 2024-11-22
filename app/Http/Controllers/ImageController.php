<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Client;

class ImageController extends Controller
{
    private $aws_secret_key;
    private $aws_access_key;
    private $client;

    public function __construct(Request $request)
    {
        $this->aws_secret_key = 'b52dcdbea046cc2cc13a5b767a1c71ea8acbe96422b3e45525d3678ce2b5ed3e';
        $this->aws_access_key = 'cbb3e2fea7c7f3e7af09b67eeec7d62c';
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

    // Upload ảnh
    public function uploadImage(Request $request)
    {
        // Lấy thông tin user đã xác thực thông qua middleware DeviceIdAuth
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return response()->json(['error' => 'Unauthorized: Invalid device_id or platform'], 401);
        }

        // Lấy device_id và platform từ customer nếu cần
        $deviceId = $customer->device_id;
        $platform = $customer->platform;

        // Kiểm tra nếu có file ảnh được gửi lên
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            $uid = Str::uuid()->toString(); // Tạo UID cho ảnh
            $fileName = $uid . '.' . $file->getClientOriginalExtension(); // Đặt tên file
            $filePath = $file->getPathname(); // Get the full file path for uploading

            // Upload the image to Cloudflare R2
            $cdnUrl = $this->uploadToCloudFlareFromFile1($filePath, 'images', $fileName);

            // If the upload fails, return an error
            if (strpos($cdnUrl, 'error:') === 0) {
                return response()->json(['error' => 'Error uploading file to Cloudflare R2: ' . $cdnUrl], 500);
            }

            // Tạo bản ghi mới trong cơ sở dữ liệu
            $image = Image::create([
                'uid' => $uid,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $cdnUrl, // Save the CDN URL
                'device_id' => $deviceId,  // Lưu device_id vào cơ sở dữ liệu (nếu cần)
                'platform' => $platform,   // Lưu platform vào cơ sở dữ liệu (nếu cần)
            ]);

            // Trả về thông tin ảnh đã upload thành công
            return response()->json([
                'uid' => $image->uid,
                'file_name' => $image->file_name,
                'api_url' => url("/api/image/{$image->uid}"),
            ], 200);
        }

        // Nếu không có ảnh hoặc ảnh không hợp lệ, trả về lỗi
        return response()->json(['error' => 'No valid file uploaded'], 400);
    }

    // Lấy ảnh dựa trên UID
    public function getImage($uid)
    {
        $image = Image::where('uid', $uid)->first();

        if ($image) {
            return response()->json([
                'file_name' => $image->file_name,
                'file_path' => $image->file_path
            ], 200);
        }

        return response()->json(['error' => 'Image not found'], 404);
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
}
