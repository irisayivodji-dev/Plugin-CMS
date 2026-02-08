<?php

namespace App\Controllers\Admin\Categories;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\CategoryRepository;

class EditCategoryController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageCategories();

        $categoryId = (int) $request->getSlug('id');
        $categoryRepository = new CategoryRepository();
        $category = $categoryRepository->find($categoryId);
        if (empty($category)) {
            \App\Lib\Auth\Session::set('flash_error', 'Catégorie non trouvée');
            return Response::redirect('/admin/categories');
        }

        // Vérifier si c'est une requête PATCH (via _method) ou POST
        $postData = $request->getPost();
        $isPatch = $request->getMethod() === 'PATCH' || 
                   ($request->getMethod() === 'POST' && isset($postData['_method']) && strtoupper($postData['_method']) === 'PATCH');
        
        if ($isPatch) {
            return $this->handlePatch($request, $category, $categoryRepository);
        }

        // GET - Afficher le formulaire
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/categories/edit', [
            'csrf_token' => $csrfToken,
            'category' => $category,
            'errors' => [],
            'old' => []
        ]);
    }

    private function handlePatch(Request $request, $category, CategoryRepository $categoryRepository): Response
    {
        $csrfToken = $request->post('csrf_token');
        if (!CsrfToken::validate($csrfToken ?? '')) {
            return $this->renderWithErrors(['csrf' => 'Token CSRF invalide'], $request->getPost(), $category);
        }

        $data = $request->getPost();
        $errors = $this->validate($data, $category->id, $categoryRepository);

        if (!empty($errors)) {
            return $this->renderWithErrors($errors, $data, $category);
        }

        // Mettre à jour la catégorie
        if (isset($data['name'])) {
            $category->name = trim($data['name']);
            // Mettre à jour le slug si le nom change
            $category->generateSlug();
        }
        if (isset($data['description'])) {
            $category->description = trim($data['description']);
        }
        $category->updated_at = date('Y-m-d H:i:s');

        try {
            $categoryRepository->update($category);
        } catch (\PDOException $e) {
            // Capturer les erreurs de contrainte unique (slug)
            if ($e->getCode() === '23505' || strpos($e->getMessage(), 'duplicate key') !== false) {
                if (strpos($e->getMessage(), 'category_slug_key') !== false) {
                    return $this->renderWithErrors(['name' => 'Ce nom génère un slug déjà utilisé. Veuillez choisir un autre nom.'], $data, $category);
                }
            }
            // Autre erreur SQL
            return $this->renderWithErrors(['name' => 'Une erreur est survenue lors de la modification. Veuillez réessayer.'], $data, $category);
        }

        // Message de succès
        \App\Lib\Auth\Session::set('flash_success', 'Catégorie modifiée avec succès');

        return Response::redirect('/admin/categories');
    }

    private function validate(array $data, int $categoryId, CategoryRepository $categoryRepository): array
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
                // Vérifier si le nom existe déjà (sauf pour la catégorie actuelle)
                $existingCategory = $categoryRepository->findByName($name);
                if ($existingCategory !== null && $existingCategory->id !== $categoryId) {
                    $errors['name'] = 'Ce nom est déjà utilisé';
                } else {
                    // Vérifier si le slug généré existe déjà (sauf pour la catégorie actuelle)
                    $tempCategory = new \App\Entities\Category();
                    $tempCategory->name = $name;
                    $tempCategory->generateSlug();
                    $slug = $tempCategory->slug;
                    
                    $existingCategoryBySlug = $categoryRepository->findBySlug($slug);
                    if ($existingCategoryBySlug !== null && $existingCategoryBySlug->id !== $categoryId) {
                        $errors['name'] = 'Ce nom génère un slug déjà utilisé. Veuillez choisir un autre nom.';
                    }
                }
            }
        }

        // Description
        if (isset($data['description'])) {
            if (empty(trim($data['description']))) {
                $errors['description'] = 'La description est requise';
            } elseif (strlen(trim($data['description'])) > 1000) {
                $errors['description'] = 'La description ne doit pas dépasser 1000 caractères';
            }
        }

        return $errors;
    }

    private function renderWithErrors(array $errors, array $old, $category): Response
    {
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/categories/edit', [
            'csrf_token' => $csrfToken,
            'category' => $category,
            'errors' => $errors,
            'old' => $old
        ]);
    }
}

?>
