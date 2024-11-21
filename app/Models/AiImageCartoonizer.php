<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiImageCartoonizer extends Model
{
    use HasFactory;

    // Đặt tên bảng nếu không theo quy ước của Laravel
    protected $table = 'ai_image_cartoonizer';

    // Các thuộc tính có thể gán
    protected $fillable = [
        'name',
        'module',
        'model_name',
        'prompt',
        'overwrite',
        'denoising_strength',
        'image_uid',
        'cn_name'
    ];

    // Các trường không thể gán (nếu có)
    protected $guarded = [];

    // Nếu cần, bạn có thể thêm các phương thức xử lý dữ liệu tại đây
}
