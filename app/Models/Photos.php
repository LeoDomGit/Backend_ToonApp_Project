<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photos extends Model
{
    use HasFactory;
    protected $table='photos';
    protected $fillable=['id','customer_id','original_image_path','deleted_at','created_at','updated_at'];
}
