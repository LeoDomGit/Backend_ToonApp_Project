<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Languages extends Model
{
    use HasFactory;
    protected $table='languages';
    protected $fillable=[
        'key',
        'en',
        'vi',
        'de',
        'ksl',
        'pl',
        'nu',
    ];
    protected $casts = [
        'en' => 'string',
        'vi' => 'string',
        'de' => 'string',
        'ksl' => 'string',
        'pl' => 'string',
        'nu' => 'string',
    ];
}
