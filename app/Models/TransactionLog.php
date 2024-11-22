<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    use HasFactory;

    // Các trường được phép fill
    protected $fillable = [
        'trans_id',
        'status',
        'uid',
        'api_token',
        'device_id',
        'platform',
        'jconfig',
    ];
}
