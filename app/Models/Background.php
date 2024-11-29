<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Background extends Model
{
    use HasFactory;
    protected $table = 'background';
    protected $fillable = ['id', 'path', 'feature_id','url_front', 'group_id', 'status', 'is_front', 'created_at', 'updated_at'];
    public function group()


    {
        return $this->belongsTo(GroupBackground::class, 'group_id');
    }
}
