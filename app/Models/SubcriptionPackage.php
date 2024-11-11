<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubcriptionPackage extends Model
{
    use HasFactory;
    protected $table = 'subcription_packages';
    protected $fillable = ['id', 'name', 'price', 'duration', 'description', 'status', 'payment_method', 'created_at', 'updated_at'];
    public function get_columns()
    {
        return ['id', 'name', 'price', 'duration', 'description', 'status', 'payment_method', 'created_at', 'updated_at'];
    }
}
