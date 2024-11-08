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
        'created_at',
        'updated_at',
    ];
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    public function feature() {
        return $this->belongsToMany(Features::class, 'features_sizes', 'size_id', 'feature_id');
    }
}
