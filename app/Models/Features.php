<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Features extends Model
{
    use HasFactory;
    protected $table = 'features';
    
    protected $fillable = [
        'id',
        'name',
        'description',
        'status',
        'created_at',
        'updated_at'
    ];

    // Define the inverse relationship with the SubFeatures model
    public function subFeatures()
    {
        return $this->hasMany(SubFeatures::class, 'feature_id', 'id'); // Specify the foreign key explicitly
    }
}
