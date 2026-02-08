<?php

namespace App\Controllers\Admin\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\ArticleRepository;

class PublishArticleController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        
        // Seuls admin/editor peuvent publier
        $this->requireCanPublishArticles();
        
        $articleId = (int) $request->getSlug('id');
        $articleRepository = new ArticleRepository();
        $article = $articleRepository->find($articleId);

        if (empty($article)) {
            \App\Lib\Auth\Session::set('flash_error', 'Article non trouvé');
            return Response::redirect('/admin/articles');
        }

        // Vérifier si c'est une requête POST
        if ($request->getMethod() === 'POST') {
            $csrfToken = $request->post('csrf_token');
            if (!CsrfToken::validate($csrfToken ?? '')) {
                \App\Lib\Auth\Session::set('flash_error', 'Token CSRF invalide');
                return Response::redirect('/admin/articles');
            }

            // Vérifier que le contenu est présent avant publication
            if (empty(trim($article->content ?? ''))) {
                \App\Lib\Auth\Session::set('flash_error', 'Le contenu est requis pour publier un article');
                return Response::redirect('/admin/articles');
            }

            // Publier l'article
            $article->status = 'published';
            if (!$article->published_at) {
                $article->published_at = date('Y-m-d H:i:s');
            }
            $article->updated_at = date('Y-m-d H:i:s');

            try {
                $articleRepository->update($article);
                \App\Lib\Auth\Session::set('flash_success', 'Article publié avec succès');
            } catch (\PDOException $e) {
                \App\Lib\Auth\Session::set('flash_error', 'Une erreur est survenue lors de la publication');
            }
        }

        return Response::redirect('/admin/articles');
    }
}

?>
