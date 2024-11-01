<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activities extends Model
{
    use HasFactory;
    protected $table='activities';
    protected $fillable=[
        'id',
        'customer_id',
        'photo_id',
        'features_id',
        'image_result',
        'image_size',
        'ai_model',
        'api_endpoint',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
