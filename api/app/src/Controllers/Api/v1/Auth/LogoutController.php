<?php

namespace App\Controllers\Api\v1\Auth;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\AuthService;
use App\Lib\Auth\CsrfToken;

class LogoutController extends AbstractController {
    public function process(Request $request): Response
    {
        $headers = $request->getHeaders();
        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';
        
        // Vérifier si c'est une requête JSON (API)
        if (strpos($contentType, 'application/json') !== false) {
            $authService = new AuthService();
            $authService->logout();

            return new Response(
                json_encode(['message' => 'Logout successful']),
                200,
                ['Content-Type' => 'application/json']
            );
        }
        
        // Requête GET (lien de déconnexion)
        if ($request->getMethod() === 'GET') {
            $authService = new AuthService();
            $authService->logout();
            return Response::redirect('/login');
        }
        
        // Requête POST (formulaire HTML avec CSRF)
        $csrfToken = $request->post('csrf_token');
        
        // Validation CSRF
        if (!CsrfToken::validate($csrfToken ?? '')) {
            return Response::redirect('/admin');
        }

        $authService = new AuthService();
        $authService->logout();

        return Response::redirect('/login');
    }
}
