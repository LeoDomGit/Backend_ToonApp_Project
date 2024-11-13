<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubFeatures extends Model
{
    use HasFactory;
    protected $table = 'sub_features';
    protected $fillable = [
        'id',
        'name',
        'slug',
        'api_endpoint',
        'remove_bg',
        'description',
        'model_id',
        'presetStyle',
        'preprocessorId',
        'strengthType',
        'initImageId',
        'is_pro',
        'detech_face',
        'status',
        'prompt',
        'image',
        'feature_id', // Make sure this matches your database column
        'created_at',
        'updated_at'
    ];
    public function feature()
    {
        return $this->belongsTo(Features::class, 'feature_id', 'id'); // Corrected foreign key
    }
}
