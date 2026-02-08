<?php

namespace App\Controllers\Admin\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\ArticleRepository;

class DeleteArticleController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        
        $articleId = (int) $request->getSlug('id');
        $articleRepository = new ArticleRepository();
        $article = $articleRepository->find($articleId);

        if (empty($article)) {
            \App\Lib\Auth\Session::set('flash_error', 'Article non trouvé');
            return Response::redirect('/admin/articles');
        }

        // Vérifier les permissions : admin/editor peuvent supprimer tous, author uniquement les siens
        $this->requireCanManageArticle($article);

        // Vérifier si c'est une requête DELETE (via _method) ou POST
        $postData = $request->getPost();
        $isDelete = $request->getMethod() === 'DELETE' || 
                    ($request->getMethod() === 'POST' && isset($postData['_method']) && strtoupper($postData['_method']) === 'DELETE');
        
        if ($isDelete || $request->getMethod() === 'POST') {
            // Vérifier CSRF pour les requêtes POST
            if ($request->getMethod() === 'POST') {
                $csrfToken = $request->post('csrf_token');
                if (!CsrfToken::validate($csrfToken ?? '')) {
                    \App\Lib\Auth\Session::set('flash_error', 'Token CSRF invalide');
                    return Response::redirect('/admin/articles');
                }
            }

            try {
                $articleRepository->remove($article);
                \App\Lib\Auth\Session::set('flash_success', 'Article supprimé avec succès');
            } catch (\PDOException $e) {
                \App\Lib\Auth\Session::set('flash_error', 'Une erreur est survenue lors de la suppression');
            }
            
            return Response::redirect('/admin/articles');
        }

        // GET - Afficher la page de confirmation
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/articles/delete', [
            'csrf_token' => $csrfToken,
            'article' => $article
        ]);
    }
}

?>
