<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturesSizes extends Model
{
    use HasFactory;
    protected $table='features_sizes';
    protected $fillable=['id','feature_id','size_id','created_at','updated_at'];
}
