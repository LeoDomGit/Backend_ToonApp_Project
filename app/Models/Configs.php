<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configs extends Model
{
    use HasFactory;
    protected $table='configs';
    protected $fillable = [
        'id',
        'domain',
        'package_name',
        'policy',
        'term',
        'support',
        'status',
        'created_at',
        'updated_at'
    ];
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
