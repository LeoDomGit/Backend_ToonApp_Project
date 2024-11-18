<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
class Customers extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'fullname',
        'email',
        'age',
        'gender',
        'remember_token',
        'place_of_birth',
        'country',
        'city',
        'platform',
        'last_login',
        'auth_provider_id',
        'auth_provider',
        'auth_email',
        'auth_token',
        'device_id',
        'expired_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_login' => 'datetime',
    ];

    public function updateRememberTokenAndExpiry($duration, $platform)
    {
        // Check if remember_token exists; if not, create a new one
        if (!$this->remember_token) {
            $this->remember_token = $this->createToken('customer_token')->plainTextToken;
        }

        // Update expired_at based on the duration
        if ($this->expired_at) {
            $this->expired_at = Carbon::parse($this->expired_at)->addDays($duration);
        } else {
            // Set a new expiry if none exists
            $this->expired_at = Carbon::now()->addDays($duration);
        }
        $this->platform = $platform;
        $this->save();
    }
}
