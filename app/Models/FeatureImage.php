<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureImage extends Model
{
    use HasFactory;
    protected $table = 'feature_images';
    protected $fillable = [
        'api_route',
        'path',
        'created_at',
        'updated_at',
    ];
}
