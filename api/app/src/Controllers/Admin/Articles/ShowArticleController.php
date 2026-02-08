<?php

namespace App\Controllers\Admin\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleRepository;
use App\Repositories\UserRepository;
use App\Repositories\MediaRepository;

class ShowArticleController extends AbstractController
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

        // Vérifier les permissions
        $this->requireCanManageArticle($article);

        // Récupérer les informations complémentaires
        $userRepository = new UserRepository();
        $author = $userRepository->find($article->author_id);
        
        $categories = $articleRepository->getCategories($article->id);
        $tags = $articleRepository->getTags($article->id);
        
        $mediaRepository = new MediaRepository();
        $media = $mediaRepository->findByArticle($article->id);
        $featuredMedia = $mediaRepository->findFeaturedByArticle($article->id);

        // Récupérer les messages flash
        $flashSuccess = \App\Lib\Auth\Session::get('flash_success');
        $flashError = \App\Lib\Auth\Session::get('flash_error');
        
        if ($flashSuccess) {
            \App\Lib\Auth\Session::remove('flash_success');
        }
        if ($flashError) {
            \App\Lib\Auth\Session::remove('flash_error');
        }

        return $this->render('admin/articles/show', [
            'article' => $article,
            'author' => $author,
            'categories' => $categories,
            'tags' => $tags,
            'media' => $media,
            'featuredMedia' => $featuredMedia,
            'canPublish' => $this->canPublishArticles(),
            'canEdit' => $this->canManageArticle($article),
            'canDelete' => $this->canManageArticle($article),
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError
        ]);
    }
}

?>
