<?php

namespace App\Lib\Auth;

use App\Repositories\UserRepository;
use App\Entities\User;
use App\Lib\Auth\Session;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function login(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            return null;
        }

        if (!$user->verifyPassword($password)) {
            return null;
        }

        Session::set('user_id', $user->id);
        Session::set('user_role', $user->role);
        
        return $user;
    }

    public function logout(): void
    {
        Session::destroy();
    }

    public function getCurrentUser(): ?User
    {
        if (!Session::isAuthenticated()) {
            return null;
        }

        $userId = Session::get('user_id');
        return $this->userRepository->find($userId);
    }

    public function hasRole(string $role): bool
    {
        $userRole = Session::get('user_role');
        return $userRole === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}