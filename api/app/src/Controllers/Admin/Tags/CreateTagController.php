<?php

namespace App\Controllers\Admin\Tags;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Entities\Tag;
use App\Repositories\TagRepository;

class CreateTagController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageTags();

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        // GET - Afficher le formulaire
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/tags/create', [
            'csrf_token' => $csrfToken,
            'errors' => [],
            'old' => []
        ]);
    }

    private function handlePost(Request $request): Response
    {
        $csrfToken = $request->post('csrf_token');
        if (!CsrfToken::validate($csrfToken ?? '')) {
            return $this->renderWithErrors(['csrf' => 'Token CSRF invalide'], $request->getPost());
        }

        $data = $request->getPost();
        $errors = $this->validate($data);

        if (!empty($errors)) {
            return $this->renderWithErrors($errors, $data);
        }

        $tagRepository = new TagRepository();

        // Vérifier si la catégorie existe déjà par nom
        $existingTag = $tagRepository->findByName(trim($data['name']));
        if ($existingTag !== null) {
            return $this->renderWithErrors(['name' => 'Ce Tag existe déjà'], $data);
        }

        // Créer le tag
        $tag = new Tag();
        $tag->name = trim($data['name']);
        $tag->description = trim($data['description'] ?? '');
        $tag->created_at = date('Y-m-d H:i:s');
        $tag->updated_at = date('Y-m-d H:i:s');
        
        // Générer le slug
        $tag->generateSlug();
        
        // Vérifier si le slug existe déjà
        $existingTagBySlug = $tagRepository->findBySlug($tag->slug);
        if ($existingTagBySlug !== null) {
            return $this->renderWithErrors(['name' => 'Ce nom génère un slug déjà utilisé. Veuillez choisir un autre nom.'], $data);
        }

        try {
            $tag->id = $tagRepository->save($tag);
        } catch (\PDOException $e) {
            // Capturer les erreurs de contrainte unique (slug)
            if ($e->getCode() === '23505' || strpos($e->getMessage(), 'duplicate key') !== false) {
                if (strpos($e->getMessage(), 'tag_slug_key') !== false) {
                    return $this->renderWithErrors(['name' => 'Ce nom génère un slug déjà utilisé. Veuillez choisir un autre nom.'], $data);
                }
            }
            // Autre erreur SQL
            return $this->renderWithErrors(['name' => 'Une erreur est survenue lors de la création. Veuillez réessayer.'], $data);
        }

        // Message de succès
        \App\Lib\Auth\Session::set('flash_success', 'Tag créé avec succès');

        return Response::redirect('/admin/tags');
    }

    private function validate(array $data): array
    {
        $errors = [];

        // Name
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Le nom est requis';
        } elseif (strlen(trim($data['name'])) > 255) {
            $errors['name'] = 'Le nom ne doit pas dépasser 255 caractères';
        }

        // Description (optionnelle)
        if (isset($data['description']) && !empty(trim($data['description']))) {
            if (strlen(trim($data['description'])) > 1000) {
                $errors['description'] = 'La description ne doit pas dépasser 1000 caractères';
            }
        }

        return $errors;
    }

    private function renderWithErrors(array $errors, array $old): Response
    {
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/tags/create', [
            'csrf_token' => $csrfToken,
            'errors' => $errors,
            'old' => $old
        ]);
    }
}

?>
