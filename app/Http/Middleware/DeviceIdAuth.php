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
        if ($deviceId) {
            // Find a customer with the given device_id
            $customer = Customers::where('device_id', $deviceId)->first();
            if ($customer) {
                // Log the customer in for this request
                Auth::guard('customer')->setUser($customer);
            }else{
                $customer = Customers::create([
                    'device_id' => $deviceId,
                    'created_at' => now(),
                ]);
                Auth::guard('customer')->setUser($customer);
            }
            return $next($request);

        }

        return response()->json([
            'message' => 'Unauthorized: Invalid device_id',
        ], 401);
    }
}
