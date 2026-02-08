<?php

namespace App\Controllers\Api\v1\Auth;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\AuthService;

class ProfileController extends AbstractController {
    public function process(Request $request): Response
    {
        $authService = new AuthService();
        $user = $authService->getCurrentUser();

        if (!$user) {
            return new Response(
                json_encode(['error' => 'Not authenticated']),
                401,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response(
            json_encode($user),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}
