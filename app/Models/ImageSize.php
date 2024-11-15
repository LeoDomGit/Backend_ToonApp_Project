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
        'width',
        'height',
        'status',
        'image',
        'created_at',
        'updated_at',
    ];
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    public function imageUrl():Attribute
    {
        return Attribute::get(function ($value, $attributes){
            return $attributes['image'] ? asset('storage/' . $attributes['image']) : null;
        });
    }
    public function feature() {
        return $this->belongsToMany(Features::class, 'features_sizes', 'size_id', 'feature_id');
    }
}
