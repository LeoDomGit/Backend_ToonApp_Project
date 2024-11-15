<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupBackground extends Model
{
    use HasFactory;
    protected $table='group_backgrounds';
    protected $fillable = [
        'name',
        'slug',
        'feature_id',
        'sub_feature_id',
        'status',
    ];

    public function feature()
    {
        return $this->belongsTo(Features::class);
    }
    public function backgrounds()
    {
        return $this->hasMany(Background::class, 'group_id'); // This assumes 'group_background_id' is the foreign key in the 'background' table
    }
    public function subFeature()
    {
        return $this->belongsTo(SubFeatures::class);
    }
}
