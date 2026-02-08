<?php

namespace App\Controllers\Admin\Users;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\UserRepository;
use App\Repositories\ArticleRepository;

class UsersController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageUsers();

        // Récupérer tous les utilisateurs
        $userRepository = new UserRepository();
        $users = $userRepository->findAll();
        
        // Récupérer le nombre d'articles par utilisateur
        $articleRepository = new ArticleRepository();
        $usersWithStats = [];
        
        foreach ($users as $user) {
            $articleCount = $articleRepository->countByAuthor($user->id);
            $usersWithStats[] = [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'article_count' => $articleCount
            ];
        }

        // Récupérer les messages flash
        $flashSuccess = \App\Lib\Auth\Session::get('flash_success');
        $flashError = \App\Lib\Auth\Session::get('flash_error');
        
        // Supprimer les messages après les avoir récupérés
        if ($flashSuccess) {
            \App\Lib\Auth\Session::remove('flash_success');
        }
        if ($flashError) {
            \App\Lib\Auth\Session::remove('flash_error');
        }

        $csrfToken = CsrfToken::generate();
        return $this->render('admin/users', [
            'csrf_token' => $csrfToken,
            'users' => $usersWithStats,
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError
        ]);
    }
}

?>
