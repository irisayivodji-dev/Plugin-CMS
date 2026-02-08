<?php

namespace App\Controllers\Admin\Media;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\MediaRepository;

class MediaController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        
        // Tous les utilisateurs authentifiés peuvent accéder à leur médiathèque
        if (!\App\Lib\Auth\Session::isAuthenticated()) {
            return Response::redirect('/login');
        }

        $authService = new \App\Lib\Auth\AuthService();
        $currentUser = $authService->getCurrentUser();

        if (!$currentUser) {
            return Response::redirect('/login');
        }

        $mediaRepository = new MediaRepository();

        // Chaque utilisateur voit uniquement sa propre médiathèque
        $media = $mediaRepository->findByUploader($currentUser->id);

        // Récupérer les messages flash
        $flashSuccess = \App\Lib\Auth\Session::get('flash_success');
        $flashError = \App\Lib\Auth\Session::get('flash_error');
        
        if ($flashSuccess) {
            \App\Lib\Auth\Session::remove('flash_success');
        }
        if ($flashError) {
            \App\Lib\Auth\Session::remove('flash_error');
        }

        return $this->render('admin/media', [
            'media' => $media,
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError
        ]);
    }
}
