<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activities extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'photo_id',
        'features_id',
        'image_result',
        'image_size',
        'ai_model',
        'api_endpoint',
        'status',
    ];
    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }

    public function feature()
    {
        return $this->belongsTo(Features::class, 'features_id');
    }

    public function imageSize()
    {
        return $this->belongsTo(ImageSize::class, 'image_size');
    }
}
 