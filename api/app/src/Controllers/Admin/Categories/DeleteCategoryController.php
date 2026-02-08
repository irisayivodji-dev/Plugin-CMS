<?php

namespace App\Controllers\Admin\Categories;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\CategoryRepository;

class DeleteCategoryController extends AbstractController
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

        // Récupérer le nombre d'articles associés à cette catégorie
        $articles = $categoryRepository->getArticles($categoryId);
        $articleCount = count($articles);

        // Vérifier si c'est une requête DELETE 
        $postData = $request->getPost();
        $isDelete = $request->getMethod() === 'DELETE' || 
                    ($request->getMethod() === 'POST' && isset($postData['_method']) && strtoupper($postData['_method']) === 'DELETE');
        
        if ($isDelete || $request->getMethod() === 'POST') {
            // Vérifier CSRF pour les requêtes POST
            if ($request->getMethod() === 'POST') {
                $csrfToken = $request->post('csrf_token');
                if (!CsrfToken::validate($csrfToken ?? '')) {
                    \App\Lib\Auth\Session::set('flash_error', 'Token CSRF invalide');
                    return Response::redirect('/admin/categories');
                }
            }

            $categoryRepository->remove($category);
            \App\Lib\Auth\Session::set('flash_success', 'Catégorie supprimée avec succès');
            return Response::redirect('/admin/categories');
        }

        // GET - Afficher la page de confirmation
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/categories/delete', [
            'csrf_token' => $csrfToken,
            'category' => $category,
            'article_count' => $articleCount
        ]);
    }
}

?>
