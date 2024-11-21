<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    // Xác định tên bảng nếu không theo chuẩn
    protected $table = 'feedback';

    // Đảm bảo Laravel chỉ mass-assign những thuộc tính này
    protected $fillable = [
        'device_id',
        'platform',
        'feedback',
        'note',
        'status'
    ];
    public function subcriptionPackages()
    {
        return $this->belongsToMany(SubcriptionPackage::class);
    }
}
