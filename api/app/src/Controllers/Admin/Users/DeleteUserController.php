<?php

namespace App\Controllers\Admin\Users;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\UserRepository;

class DeleteUserController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageUsers();

        $userId = (int) $request->getSlug('id');
        $userRepository = new UserRepository();
        $user = $userRepository->find($userId);

        if (empty($user)) {
            \App\Lib\Auth\Session::set('flash_error', 'Utilisateur non trouvé');
            return Response::redirect('/admin/users');
        }

        // Vérifier que l'utilisateur ne se supprime pas lui-même
        $currentUserId = \App\Lib\Auth\Session::get('user_id');
        if ($user->id === $currentUserId) {
            \App\Lib\Auth\Session::set('flash_error', 'Vous ne pouvez pas supprimer votre propre compte');
            return Response::redirect('/admin/users');
        }

        // Vérifier si c'est une requête DELETE (via _method) ou POST/DELETE direct
        $postData = $request->getPost();
        $isDelete = $request->getMethod() === 'DELETE' || 
                    ($request->getMethod() === 'POST' && isset($postData['_method']) && strtoupper($postData['_method']) === 'DELETE');
        
        if ($isDelete || $request->getMethod() === 'POST') {
            // Vérifier CSRF pour les requêtes POST
            if ($request->getMethod() === 'POST') {
                $csrfToken = $request->post('csrf_token');
                if (!CsrfToken::validate($csrfToken ?? '')) {
                    \App\Lib\Auth\Session::set('flash_error', 'Token CSRF invalide');
                    return Response::redirect('/admin/users');
                }
            }

            $userRepository->remove($user);
            \App\Lib\Auth\Session::set('flash_success', 'Utilisateur supprimé avec succès');
            return Response::redirect('/admin/users');
        }

        // GET - Afficher la page de confirmation
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/users/delete', [
            'csrf_token' => $csrfToken,
            'user' => $user
        ]);
    }
}

?>
