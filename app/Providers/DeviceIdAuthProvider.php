<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DeviceIdAuthProvider extends ServiceProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        if (isset($credentials['device_id'])) {
            return $this->createModel()
                        ->newQuery()
                        ->where('device_id', $credentials['device_id'])
                        ->first();
        }

        return null;
    }
}
