<?php

namespace App\Controllers\Admin\Categories;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\CategoryRepository;
use App\Repositories\ArticleRepository;

class CategoriesController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        $this->requireCanManageCategories();

        // Récupérer toutes les categories
        $categoryRepository = new CategoryRepository();
        $categories = $categoryRepository->findAll();

        // Récupérer le nombre d'articles par categories
        $articleRepository = new ArticleRepository();
        $categoriesWithStats = [];
        
        foreach ($categories as $category) {
            $articleCount = $articleRepository->countByCategory($category->id);
            $categoriesWithStats[] = [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'slug' => $category->slug,
                'created_at' => $category->created_at,
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
        return $this->render('admin/categories', [
            'csrf_token' => $csrfToken,
            'categories' => $categoriesWithStats,
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError
        ]);
    }
}

?>
