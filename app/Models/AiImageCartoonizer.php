<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiImageCartoonizer extends Model
{
    use HasFactory;

    // Tên bảng (nếu tên bảng khác với tên model viết hoa)
    protected $table = 'ai_image_cartoonizer';

    // Các thuộc tính có thể được fillable
    protected $fillable = [
        'name',
        'module',
        'model_name',
        'prompt',
        'overwrite',
        'denoising_strength',
        'image_uid',
        'cn_name',
        'apiKey',
        'trans_id',

    ];
}
