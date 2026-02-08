<?php

namespace App\Controllers\Admin\Categories;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Entities\Category;
use App\Repositories\CategoryRepository;

class CreateCategoryController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageCategories();

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        // GET - Afficher le formulaire
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/categories/create', [
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

        $categoryRepository = new CategoryRepository();

        // Vérifier si la catégorie existe déjà par nom
        $existingCategory = $categoryRepository->findByName(trim($data['name']));
        if ($existingCategory !== null) {
            return $this->renderWithErrors(['name' => 'Cette catégorie existe déjà'], $data);
        }

        // Créer la catégorie
        $category = new Category();
        $category->name = trim($data['name']);
        $category->description = trim($data['description']);
        $category->created_at = date('Y-m-d H:i:s');
        $category->updated_at = date('Y-m-d H:i:s');
        
        // Générer le slug
        $category->generateSlug();
        
        // Vérifier si le slug existe déjà
        $existingCategoryBySlug = $categoryRepository->findBySlug($category->slug);
        if ($existingCategoryBySlug !== null) {
            return $this->renderWithErrors(['name' => 'Ce nom génère un slug déjà utilisé. Veuillez choisir un autre nom.'], $data);
        }

        try {
            $category->id = $categoryRepository->save($category);
        } catch (\PDOException $e) {
            // Capturer les erreurs de contrainte unique (slug)
            if ($e->getCode() === '23505' || strpos($e->getMessage(), 'duplicate key') !== false) {
                if (strpos($e->getMessage(), 'category_slug_key') !== false) {
                    return $this->renderWithErrors(['name' => 'Ce nom génère un slug déjà utilisé. Veuillez choisir un autre nom.'], $data);
                }
            }
            // Autre erreur SQL
            return $this->renderWithErrors(['name' => 'Une erreur est survenue lors de la création. Veuillez réessayer.'], $data);
        }

        // Message de succès
        \App\Lib\Auth\Session::set('flash_success', 'Catégorie créée avec succès');

        return Response::redirect('/admin/categories');
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

        // Description
        if (empty(trim($data['description'] ?? ''))) {
            $errors['description'] = 'La description est requise';
        } elseif (strlen(trim($data['description'])) > 1000) {
            $errors['description'] = 'La description ne doit pas dépasser 1000 caractères';
        }

        return $errors;
    }

    private function renderWithErrors(array $errors, array $old): Response
    {
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/categories/create', [
            'csrf_token' => $csrfToken,
            'errors' => $errors,
            'old' => $old
        ]);
    }
}

?>
