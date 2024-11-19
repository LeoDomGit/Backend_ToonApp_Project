<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Effects extends Model
{
    use HasFactory;
    protected $table='effects';
    protected $fillable=[
        'id',
        'name',
        'slug',
        'image',
        'status',
        'deleted_at',
        'created_at',
        'updated_at'
    ];
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
