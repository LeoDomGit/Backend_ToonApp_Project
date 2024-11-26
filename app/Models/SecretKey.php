<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecretKey extends Model
{
    use HasFactory;

    protected $table = 'secret_keys';

    // Định nghĩa các cột có thể mass assignable
    protected $fillable = [
        'api_key',
        'secret_key',
        'is_active',
    ];
}
