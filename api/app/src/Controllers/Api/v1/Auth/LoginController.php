<?php

namespace App\Controllers\Api\v1\Auth;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\AuthService;

class LoginController extends AbstractController {
    public function process(Request $request): Response
    {
        $headers = $request->getHeaders();
        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';
        
        // Vérifier si c'est une requête JSON (API)
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($request->getPayload(), true);
            
            if (!isset($data['email']) || !isset($data['password'])) {
                return new Response(
                    json_encode(['error' => 'Email and password required']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }

            $authService = new AuthService();
            $user = $authService->login($data['email'], $data['password']);

            if (!$user) {
                return new Response(
                    json_encode(['error' => 'Invalid credentials']),
                    401,
                    ['Content-Type' => 'application/json']
                );
            }

            return new Response(
                json_encode([
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'role' => $user->role
                    ]
                ]),
                200,
                ['Content-Type' => 'application/json']
            );
        }
        
        // Requête POST (formulaire HTML)
        $csrfToken = $request->post('csrf_token');
        $email = $request->post('email');
        $password = $request->post('password');

        // Validation CSRF
        if (!\App\Lib\Auth\CsrfToken::validate($csrfToken ?? '')) {
            $csrfToken = \App\Lib\Auth\CsrfToken::generate();
            return $this->render('auth/login', [
                'error' => 'Token de sécurité invalide. Veuillez réessayer.',
                'csrf_token' => $csrfToken
            ]);
        }

        if (!$email || !$password) {
            $csrfToken = \App\Lib\Auth\CsrfToken::generate();
            return $this->render('auth/login', [
                'error' => 'Email et mot de passe requis',
                'csrf_token' => $csrfToken
            ]);
        }

        // Validation email côté serveur
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $csrfToken = \App\Lib\Auth\CsrfToken::generate();
            return $this->render('auth/login', [
                'error' => 'Format d\'email invalide',
                'csrf_token' => $csrfToken
            ]);
        }

        $authService = new AuthService();
        $user = $authService->login($email, $password);

        if (!$user) {
            $csrfToken = \App\Lib\Auth\CsrfToken::generate();
            return $this->render('auth/login', [
                'error' => 'Identifiants invalides',
                'csrf_token' => $csrfToken
            ]);
        }

        return Response::redirect('/admin');
    }
}
