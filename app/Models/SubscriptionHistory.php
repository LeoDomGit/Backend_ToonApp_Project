<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionHistory extends Model
{
    use HasFactory;
    protected $table='subscription_history';
    protected $fillable = ['customer_id','subscription_package_id','login_provider','serverVerificationData','auth_method','created_at','updated_at','deleted_at'];
}
