<?php

namespace App\Controllers\Admin\Media;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\MediaRepository;
use App\Entities\Media;

class UploadMediaController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        
        // Tous les utilisateurs authentifiés peuvent uploader des médias
        if (!\App\Lib\Auth\Session::isAuthenticated()) {
            return Response::redirect('/login');
        }

        if ($request->getMethod() === 'POST') {
            return $this->handleUpload($request);
        }

        $csrfToken = CsrfToken::generate();
        return $this->render('admin/media/upload', [
            'csrf_token' => $csrfToken,
            'errors' => [],
            'old' => []
        ]);
    }

    private function handleUpload(Request $request): Response
    {
        $csrfToken = $request->post('csrf_token');
        if (!CsrfToken::validate($csrfToken ?? '')) {
            return $this->renderWithErrors(['csrf' => 'Token CSRF invalide'], []);
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return $this->renderWithErrors(['file' => 'Erreur lors de l\'upload du fichier'], []);
        }

        $file = $_FILES['file'];
        $errors = $this->validateFile($file);

        if (!empty($errors)) {
            return $this->renderWithErrors($errors, []);
        }

        $authService = new \App\Lib\Auth\AuthService();
        $currentUser = $authService->getCurrentUser();

        // Créer le dossier uploads s'il n'existe pas
        $uploadDir = __DIR__ . '/../../../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Générer un nom de fichier unique pour éviter les conflits
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $filename;

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return $this->renderWithErrors(['file' => 'Impossible de sauvegarder le fichier'], []);
        }

        // Créer l'entité Media
        $media = new Media();
        $media->filename = $file['name'];
        $media->file_path = $filename;
        $media->mime_type = $file['type'];
        $media->file_type = $media->determineFileType($file['type']);
        $media->file_size = $file['size'];
        $media->alt_text = trim($request->post('alt_text') ?? '');
        $media->title = trim($request->post('title') ?? '');
        $media->description = trim($request->post('description') ?? '');
        $media->uploaded_by = $currentUser ? $currentUser->id : null;
        $media->created_at = date('Y-m-d H:i:s');
        $media->updated_at = date('Y-m-d H:i:s');

        try {
            $mediaRepository = new MediaRepository();
            $media->id = $mediaRepository->save($media);

            \App\Lib\Auth\Session::set('flash_success', 'Média uploadé avec succès');
            return Response::redirect('/admin/media');
        } catch (\PDOException $e) {
            // Supprimer le fichier en cas d'erreur
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return $this->renderWithErrors(['file' => 'Une erreur est survenue lors de l\'enregistrement'], []);
        }
    }

    private function validateFile(array $file): array
    {
        $errors = [];

        // Vérifier le type MIME
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/webm',
            'application/pdf',
            'audio/mpeg', 'audio/wav'
        ];

        if (!in_array($file['type'], $allowedMimes)) {
            $errors['file'] = 'Type de fichier non autorisé';
            return $errors;
        }

        // Limites de taille selon le type de fichier
        $mimeType = $file['type'];
        $fileSize = $file['size'];
        
        if (strpos($mimeType, 'image/') === 0) {
            // Images : max 5MB
            $maxSize = 5 * 1024 * 1024;
            if ($fileSize > $maxSize) {
                $errors['file'] = 'L\'image est trop volumineuse (max 5MB)';
                return $errors;
            }
        } elseif (strpos($mimeType, 'video/') === 0) {
            // Vidéos : max 50MB
            $maxSize = 50 * 1024 * 1024;
            if ($fileSize > $maxSize) {
                $errors['file'] = 'La vidéo est trop volumineuse (max 50MB)';
                return $errors;
            }
        } elseif (strpos($mimeType, 'audio/') === 0) {
            // Audio : max 10MB
            $maxSize = 10 * 1024 * 1024;
            if ($fileSize > $maxSize) {
                $errors['file'] = 'Le fichier audio est trop volumineux (max 10MB)';
                return $errors;
            }
        } elseif ($mimeType === 'application/pdf') {
            // PDF : max 10MB
            $maxSize = 10 * 1024 * 1024;
            if ($fileSize > $maxSize) {
                $errors['file'] = 'Le fichier PDF est trop volumineux (max 10MB)';
                return $errors;
            }
        }

        // Vérifier aussi la limite PHP upload_max_filesize
        $phpMaxSize = $this->getPhpMaxUploadSize();
        if ($fileSize > $phpMaxSize) {
            $errors['file'] = 'Le fichier dépasse la limite PHP configurée (' . $this->formatBytes($phpMaxSize) . ')';
            return $errors;
        }

        return $errors;
    }

    /**
     * Récupère la limite maximale d'upload PHP
     */
    private function getPhpMaxUploadSize(): int
    {
        $uploadMax = $this->parseSize(ini_get('upload_max_filesize'));
        $postMax = $this->parseSize(ini_get('post_max_size'));
        
        // Retourner la plus petite des deux valeurs
        return min($uploadMax, $postMax);
    }

    /**
     * Convertit une taille PHP (ex: "10M") en bytes
     */
    private function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $value = (int) $size;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
                // fallthrough
            case 'm':
                $value *= 1024;
                // fallthrough
            case 'k':
                $value *= 1024;
                break;
            default:
                // Déjà en bytes
                break;
        }
        
        return $value;
    }

    /**
     * Formate une taille en bytes en format lisible
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function renderWithErrors(array $errors, array $old): Response
    {
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/media/upload', [
            'csrf_token' => $csrfToken,
            'errors' => $errors,
            'old' => $old
        ]);
    }
}
