<?php

namespace App\Lib\Auth;

use App\Repositories\UserRepository;
use App\Entities\User;
use App\Lib\Auth\Session;

class PermissionService{
    
    // Récupèrer le rôle de l'utilisateur actuel
    
    private static function getUserRole(): ?string
    {
        if (!Session::isAuthenticated()) {
            return null;
        }
        return Session::get('user_role');
    }
    
    // Vérifier si l'utilisateur a un des rôles spécifiés
    
    private static function hasRole(array $allowedRoles): bool
    {
        $userRole = self::getUserRole();
        if ($userRole === null) {
            return false;
        }
        return in_array($userRole, $allowedRoles);
    }
    
    // Vérifier si l'utilisateur peut gérer les utilisateurs (admin uniquement)
    public static function canManageUsers(): bool
    {
        return self::hasRole(['admin']);
    }
    
    // Vérifier si l'utilisateur peut gérer les catégories (admin + editor)
    public static function canManageCategories(): bool
    {
        return self::hasRole(['admin', 'editor']);
    }
    
    // Vérifier si l'utilisateur peut gérer les tags (admin + editor)
    public static function canManageTags(): bool
    {
        return self::hasRole(['admin', 'editor']);
    }
    
    // Vérifier si l'utilisateur peut gérer tous les articles (admin + editor)
    public static function canManageAllArticles(): bool
    {
        return self::hasRole(['admin', 'editor']);
    }
    
    // Vérifier si l'utilisateur peut publier des articles (admin + editor)
    public static function canPublishArticles(): bool
    {
        return self::hasRole(['admin', 'editor']);
    }
    
    // Vérifier si l'utilisateur peut gérer ses propres articles (tous les rôles authentifiés)
    public static function canManageOwnArticles(): bool
    {
        return self::hasRole(['admin', 'editor', 'author']);
    }
    
    // Vérifier si l'utilisateur est admin
    public static function isAdmin(): bool
    {
        return self::hasRole(['admin']);
    }
    
    // Vérifier si l'utilisateur est editor
    public static function isEditor(): bool
    {
        return self::hasRole(['editor']);
    }
    
    // Vérifier si l'utilisateur est author
    public static function isAuthor(): bool
    {
        return self::hasRole(['author']);
    }
    
    // Vérifier si l'utilisateur peut accéder à une route
    public static function canAccess(string $route): bool
    {
        switch($route) {
            case 'admin/users':
                return self::canManageUsers();
            case 'admin/categories':
                return self::canManageCategories();
            case 'admin/tags':
                return self::canManageTags();
            case 'admin/articles':
                return self::canManageAllArticles();
            case 'admin/articles/publish':
                return self::canPublishArticles();
            case 'admin':
            case 'admin/dashboard':
                // Dashboard accessible à tous les utilisateurs authentifiés
                return Session::isAuthenticated();
            default:
                return false;
        }
    }
}


?>