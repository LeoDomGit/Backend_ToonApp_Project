<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Customers;
use Illuminate\Support\Facades\Auth;
class DeviceIdAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = $request->input('device_id');
        $platform = $request->input('platform');

        if ($deviceId && $platform) {
            $customer = Customers::where('device_id', $deviceId)->where('platform', $platform)->first();
            if ($customer) {
                Auth::guard('customer')->setUser($customer);
            }else{
                $customer = Customers::create([
                    'device_id' => $deviceId,
                    'platform' => $platform,
                    'created_at' => now(),
                ]);
                Auth::guard('customer')->setUser($customer);
            }
            return $next($request);

        }

        return response()->json([
            'status'=>'error',
            'msg' => 'Unauthorized: Invalid device_id or platform',
        ], 401);
    }
}
