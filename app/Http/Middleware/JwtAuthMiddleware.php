<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Try to authenticate the user using JWT
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {

            return errors('Token is invalid or expired', ['error' => 'Token is invalid or expired'],Response::HTTP_UNAUTHORIZED);
        }

        // Attach the user to the request so it can be accessed in controllers
        $request->merge(['user' => $user]);

        // Proceed with the request
        return $next($request);
    }
}
