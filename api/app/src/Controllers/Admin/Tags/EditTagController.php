<?php

namespace App\Controllers\Admin\Tags;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\TagRepository;

class EditTagController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageTags();

        $tagId = (int) $request->getSlug('id');
        $tagRepository = new TagRepository();
        $tag = $tagRepository->find($tagId);
        if (empty($tag)) {
            \App\Lib\Auth\Session::set('flash_error', 'Tag non trouvé');
            return Response::redirect('/admin/tags');
        }

        // Vérifier si c'est une requête PATCH (via _method) ou POST
        $postData = $request->getPost();
        $isPatch = $request->getMethod() === 'PATCH' || 
                   ($request->getMethod() === 'POST' && isset($postData['_method']) && strtoupper($postData['_method']) === 'PATCH');
        
        if ($isPatch) {
            return $this->handlePatch($request, $tag, $tagRepository);
        }

        // GET - Afficher le formulaire
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/tags/edit', [
            'csrf_token' => $csrfToken,
            'tag' => $tag,
            'errors' => [],
            'old' => []
        ]);
    }

    private function handlePatch(Request $request, $tag, TagRepository $tagRepository): Response
    {
        $csrfToken = $request->post('csrf_token');
        if (!CsrfToken::validate($csrfToken ?? '')) {
            return $this->renderWithErrors(['csrf' => 'Token CSRF invalide'], $request->getPost(), $tag);
        }

        $data = $request->getPost();
        $errors = $this->validate($data, $tag->id, $tagRepository);
        if (!empty($errors)) {
            return $this->renderWithErrors($errors, $data, $tag);
        }

        // Mettre à jour le tag
        if (isset($data['name'])) {
            $tag->name = trim($data['name']);
            // Mettre à jour le slug si le nom change
            $tag->generateSlug();
        }
        if (isset($data['description'])) {
            $tag->description = trim($data['description'] ?? '');
        }
        $tag->updated_at = date('Y-m-d H:i:s');

        try {
            $tagRepository->update($tag);
        } catch (\PDOException $e) {
            // Capturer les erreurs de contrainte unique (slug)
            if ($e->getCode() === '23505' || strpos($e->getMessage(), 'duplicate key') !== false) {
                if (strpos($e->getMessage(), 'tag_slug_key') !== false) {
                    return $this->renderWithErrors(['name' => 'Ce nom génère un slug déjà utilisé. Veuillez choisir un autre nom.'], $data, $tag);
                }
            }
            // Autre erreur SQL
            return $this->renderWithErrors(['name' => 'Une erreur est survenue lors de la modification. Veuillez réessayer.'], $data, $tag);
        }

        // Message de succès
        \App\Lib\Auth\Session::set('flash_success', 'Tag modifié avec succès');
        return Response::redirect('/admin/tags');
    }

    private function validate(array $data, int $tagId, TagRepository $tagRepository): array
    {
        $errors = [];

        // Name
        if (isset($data['name'])) {
            if (empty(trim($data['name']))) {
                $errors['name'] = 'Le nom est requis';
            } elseif (strlen(trim($data['name'])) > 255) {
                $errors['name'] = 'Le nom ne doit pas dépasser 255 caractères';
            } else {
                $name = trim($data['name']);
                // Vérifier si le nom existe déjà (sauf pour la tag actuelle)
                $existingTag = $tagRepository->findByName($name);
                if ($existingTag !== null && $existingTag->id !== $tagId) {
                    $errors['name'] = 'Ce nom est déjà utilisé';
                } else {
                    // Vérifier si le slug généré existe déjà (sauf pour la tag actuelle)
                    $tempTag = new \App\Entities\Tag();
                    $tempTag->name = $name;
                    $tempTag->generateSlug();
                    $slug = $tempTag->slug;
                    
                    $existingTagBySlug = $tagRepository->findBySlug($slug);
                    if ($existingTagBySlug !== null && $existingTagBySlug->id !== $tagId) {
                        $errors['name'] = 'Ce nom génère un slug déjà utilisé. Veuillez choisir un autre nom.';
                    }
                }
            }
        }

        // Description (optionnelle)
        if (isset($data['description']) && !empty(trim($data['description']))) {
            if (strlen(trim($data['description'])) > 1000) {
                $errors['description'] = 'La description ne doit pas dépasser 1000 caractères';
            }
        }

        return $errors;
    }

    private function renderWithErrors(array $errors, array $old, $tag): Response
    {
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/tags/edit', [
            'csrf_token' => $csrfToken,
            'tag' => $tag,
            'errors' => $errors,
            'old' => $old
        ]);
    }
}

?>
