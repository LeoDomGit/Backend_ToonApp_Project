<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Photo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'original_image_path',
        'edit_image_path',
        'image_size',
    ];
    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }

    public function imageSize()
    {
        return $this->belongsTo(ImageSize::class, 'image_size');
    }
}
