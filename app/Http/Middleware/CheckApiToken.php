<?php

// app/Http/Middleware/CheckApiToken.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization'); // "Bearer <token>"
        logger($token);
        if ($token !== 'Bearer secret_microservice_123') {
            return response()->json(['error' => 'Unauthorizedo'], 401);
        }
        return $next($request);
    }
}
