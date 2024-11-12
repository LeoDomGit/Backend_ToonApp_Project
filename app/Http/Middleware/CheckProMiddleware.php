<?php

namespace App\Http\Middleware;

use App\Models\Customers;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class CheckProMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('customer');

        if (!$guard->check()) {
            return response()->json(['Authorization' => 'Unauthorized'], 401);
        }

        $user = $guard->user();

        if (!$user->remember_token) {
            return response()->json(['Authorization' => 'Not Accepted'], 401);
        }

        if ($user->expired_at && Carbon::parse($user->expired_at)->isPast()) {
            Customers::where('id', $user->id)->update(['remember_token' => null,'updated_at'=>now()]);
            return response()->json(['Authorization' => 'Expired'], 401);
        }
        return $next($request);
    }
}
