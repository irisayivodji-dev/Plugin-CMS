<?php

namespace App\Lib\Http\Middleware;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Auth\Session;

class AuthMiddleware
{
    public static function handle(Request $request): ?Response
    {
        if (!Session::isAuthenticated()) {
            return new Response(
                json_encode(['error' => 'Authentication required']),
                401,
                ['Content-Type' => 'application/json']
            );
        }
        
        return null;
    }
}
