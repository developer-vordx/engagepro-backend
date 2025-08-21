<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use App\Helper;
use Closure;

class JwtUserAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = Auth::guard('user')->authenticate();
        } catch (\Exception $e) {
            return Helper::response('Invalid Token ', 'Authentication token is invalid or expired', Response::HTTP_UNAUTHORIZED);
        }
        $request->merge(['user' => $user]);
        return $next($request);
    }
}
