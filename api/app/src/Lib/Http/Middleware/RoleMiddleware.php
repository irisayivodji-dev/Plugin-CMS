<?php

namespace App\Lib\Http\Middleware;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Auth\Session;

class RoleMiddleware
{
    //Vérifie si l'utilisateur a un des rôles autorisés
     
    public static function requireRoles(Request $request, array $allowedRoles): ?Response
    {
        if (!Session::isAuthenticated()) {
            // Détecter si c'est une requête API ou HTML
            $headers = $request->getHeaders();
            $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';
            $accept = $headers['Accept'] ?? $headers['accept'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false || strpos($accept, 'application/json') !== false) {
                return new Response(
                    json_encode(['error' => 'Authentication required']),
                    401,
                    ['Content-Type' => 'application/json']
                );
            }
            
            // Redirection pour les requêtes HTML
            return Response::redirect('/login');
        }
        
        $userRole = Session::get('user_role');
        
        if (!in_array($userRole, $allowedRoles)) {
            // Détecter si c'est une requête API ou HTML
            $headers = $request->getHeaders();
            $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';
            $accept = $headers['Accept'] ?? $headers['accept'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false || strpos($accept, 'application/json') !== false) {
                return new Response(
                    json_encode(['error' => 'Insufficient permissions']),
                    403,
                    ['Content-Type' => 'application/json']
                );
            }
            
            // Redirection vers page 403 pour les requêtes HTML
            return Response::redirect('/403');
        }
        
        return null; // Accès autorisé
    }
    
    //Vérifie si l'utilisateur a un rôle spécifique (méthode legacy)
    
    public static function handle(Request $request, string $requiredRole): ?Response
    {
        return self::requireRoles($request, [$requiredRole]);
    }
    
    public static function isAdmin(Request $request): ?Response
    {
        return self::requireRoles($request, ['admin']);
    }
    
    public static function isEditor(Request $request): ?Response
    {
        return self::requireRoles($request, ['editor']);
    }
    
    public static function isAuthor(Request $request): ?Response
    {
        return self::requireRoles($request, ['author']);
    }
    
    //Vérifie si l'utilisateur est admin ou editor
     
    public static function isAdminOrEditor(Request $request): ?Response
    {
        return self::requireRoles($request, ['admin', 'editor']);
    }
}
