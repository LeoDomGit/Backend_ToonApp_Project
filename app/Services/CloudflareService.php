<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Exception\S3Exception;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected $aws_secret_key;
    protected $aws_access_key;
    protected $client;

    public function __construct()
    {
        $this->aws_secret_key = config('services.cloudflare.aws_secret_key');
        $this->aws_access_key = config('services.cloudflare.aws_access_key');
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://" . config('services.cloudflare.account_id') . ".r2.cloudflarestorage.com",
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $this->aws_access_key,
                'secret' => $this->aws_secret_key,
            ]
        ]);
    }

    public function uploadToCloudFlareFromFile($imageFile, $folder, $filename)
    {
        try {
            if (!file_exists($imageFile)) {
                Log::error('File does not exist: ' . $imageFile);
                return 'error: file does not exist';
            }

            $filename = str_replace(' ', '', $filename);
            $r2bucket = config('services.cloudflare.bucket');
            $r2object = $folder . '/' . $filename;

            $result = $this->client->putObject([
                'Bucket' => $r2bucket,
                'Key' => $r2object,
                'Body' => fopen($imageFile, 'rb'),
                'ContentType' => 'image/jpeg',
            ]);

            $cdnUrl = config('services.cloudflare.cdn_url') . "/$folder/$filename";
            return $cdnUrl;
        } catch (S3Exception $e) {
            Log::error("Error uploading file: " . $e->getMessage());
            return 'error: ' . $e->getMessage();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return 'error: ' . $th->getMessage();
        }
    }
}
