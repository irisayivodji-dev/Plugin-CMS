<?php

namespace App\Controllers\Admin\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\ArticleRepository;
use App\Lib\Auth\AuthService;

class ArticlesController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        
        // Tous les utilisateurs authentifiés peuvent voir leurs articles
        // Mais seuls admin/editor peuvent voir tous les articles
        if (!\App\Lib\Auth\Session::isAuthenticated()) {
            return Response::redirect('/login');
        }

        $articleRepository = new ArticleRepository();
        $authService = new AuthService();
        $currentUser = $authService->getCurrentUser();

        if (!$currentUser) {
            return Response::redirect('/login');
        }

        // Récupérer les articles selon les permissions
        if ($this->canManageAllArticles()) {
            // Admin/Editor : voir tous les articles
            $articles = $articleRepository->findAll();
        } else {
            // Author : voir uniquement ses propres articles
            $articles = $articleRepository->findByAuthor($currentUser->id);
        }

        // Récupérer les informations des auteurs et catégories pour chaque article
        $userRepository = new \App\Repositories\UserRepository();
        $articlesWithDetails = [];
        foreach ($articles as $article) {
            $author = $userRepository->find($article->author_id);
            $categories = $articleRepository->getCategories($article->id);
            $categoryNames = array_map(fn($cat) => $cat->name, $categories);
            
            $articlesWithDetails[] = [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'status' => $article->status,
                'author_id' => $article->author_id,
                'author_name' => $author ? ($author->firstname . ' ' . $author->lastname) : 'Inconnu',
                'categories' => $categoryNames,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
                'published_at' => $article->published_at
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
        return $this->render('admin/articles', [
            'csrf_token' => $csrfToken,
            'articles' => $articlesWithDetails,
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError,
            'canManageAll' => $this->canManageAllArticles(),
            'canPublish' => $this->canPublishArticles()
        ]);
    }
}

?>
