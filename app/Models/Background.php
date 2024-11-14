<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Background extends Model
{
    use HasFactory;
    protected $table='background';
    protected $fillable=['id','path','feature_id','is_front','status','created_at','updated_at'];
}
