<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubFeatures extends Model
{
    use HasFactory;
    protected $table = 'sub_features';
    protected $fillable = [
        'id',
        'name',
        'description',
        'status',
        'feature_id', // Make sure this matches your database column
        'created_at',
        'updated_at'
    ];
    public function feature()
    {
        return $this->belongsTo(Features::class, 'feature_id', 'id'); // Corrected foreign key
    }
}
