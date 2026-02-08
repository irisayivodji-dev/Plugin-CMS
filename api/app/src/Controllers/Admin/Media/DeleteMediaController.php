<?php

namespace App\Controllers\Admin\Media;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\MediaRepository;

class DeleteMediaController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        
        // Tous les utilisateurs authentifiés peuvent supprimer leurs médias
        if (!\App\Lib\Auth\Session::isAuthenticated()) {
            return Response::redirect('/login');
        }

        $authService = new \App\Lib\Auth\AuthService();
        $currentUser = $authService->getCurrentUser();

        if (!$currentUser) {
            return Response::redirect('/login');
        }

        $mediaId = (int) $request->getSlug('id');
        $mediaRepository = new MediaRepository();
        $media = $mediaRepository->find($mediaId);

        if (empty($media)) {
            \App\Lib\Auth\Session::set('flash_error', 'Média non trouvé');
            return Response::redirect('/admin/media');
        }

        // Vérifier que l'utilisateur peut supprimer ce média
        // Chaque utilisateur ne peut supprimer que ses propres médias
        if ($media->uploaded_by !== $currentUser->id) {
            \App\Lib\Auth\Session::set('flash_error', 'Vous n\'avez pas la permission de supprimer ce média');
            return Response::redirect('/admin/media');
        }

        // Vérifier si c'est une requête POST (confirmation)
        if ($request->getMethod() === 'POST') {
            return $this->handleDelete($request, $media, $mediaRepository);
        }

        // GET - Afficher la page de confirmation
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/media/delete', [
            'csrf_token' => $csrfToken,
            'media' => $media
        ]);
    }

    private function handleDelete(Request $request, $media, MediaRepository $mediaRepository): Response
    {
        $csrfToken = $request->post('csrf_token');
        if (!CsrfToken::validate($csrfToken ?? '')) {
            \App\Lib\Auth\Session::set('flash_error', 'Token CSRF invalide');
            return Response::redirect('/admin/media');
        }

        try {
            // Supprimer le fichier physique
            $filePath = __DIR__ . '/../../../../uploads/' . $media->file_path;
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Supprimer de la base de données (les relations article_media seront supprimées en cascade)
            $mediaRepository->remove($media);

            \App\Lib\Auth\Session::set('flash_success', 'Média supprimé avec succès');
        } catch (\Exception $e) {
            \App\Lib\Auth\Session::set('flash_error', 'Une erreur est survenue lors de la suppression');
        }

        return Response::redirect('/admin/media');
    }
}
