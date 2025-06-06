<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LanguageList extends Model
{
    use HasFactory;
    protected $table='languages_list';
    protected $fillable=['id','language','key','status','created_at','updated_at'];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
