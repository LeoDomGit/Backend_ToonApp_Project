<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Features extends Model
{
    use HasFactory;
    protected $table = 'features';

    protected $fillable = [
        'id',
        'name',
        'model_id',
        'prompt',
        'presetStyle',
        'initImageId',
        'preprocessorId',
        'strengthType',
        'description',
        'slug',
        'detech_face',
        'remove_bg',
        'status',
        'image',
        'created_at',
        'updated_at',
        'api_endpoint',
        'image',
    ];

    // Định nghĩa mối quan hệ với model SubFeatures
    public function subFeatures()
    {
        return $this->hasMany(SubFeatures::class, 'feature_id', 'id');
    }

    // Thêm accessor để trả về URL đầy đủ của ảnh
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function ($value, $attributes) {
            return $attributes['image'] ? asset('storage/' . $attributes['image']) : null;
        });
    }
}
