<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageSize extends Model
{
    use HasFactory;
    protected $table='image_sizes';
    protected $fillable=[
        'id',
        'size',
        'status',
        'created_at',
        'updated_at',
    ];
}
