<?php

namespace App\Http\Middleware\Api\V1;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->bearerToken() && $request->user("sanctum")){
            return response()->json([
                "message" => "You are already authenticated. Please log out first."
            ],400);
        }
        return $next($request);
    }
}
