<?php

namespace App\Controllers\Admin;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\Session;
use App\Lib\Auth\AuthService;
use App\Lib\Auth\CsrfToken;
use App\Repositories\UserRepository;
use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\TagRepository;
use App\Repositories\MediaRepository;
class AdminController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        
        // tous les utilisateurs authentifiés peuvent accéder au dashboard
        if (!Session::isAuthenticated()) {
            return Response::redirect('/login');
        }

        $authService = new AuthService();
        $currentUser = $authService->getCurrentUser();

        $articleRepository = new ArticleRepository();
        $categoryRepository = new CategoryRepository();
        $tagRepository = new TagRepository();
        $mediaRepository = new MediaRepository();
        $userRepository = new UserRepository();

        $stats = [];

        if($this->canManageAllArticles()){
            $stats['articles'] = [
                'total' => count($articleRepository->findAll()),
                'drafts' => count($articleRepository->findByStatus('draft')),
                'published' => count($articleRepository->findByStatus('published')),
                'archived' => count($articleRepository->findByStatus('archived')),
            ];
        }else{
            $myArticles = $articleRepository->findByAuthor($currentUser->id);
            $stats['articles'] = [
                'total' => count($myArticles),
                'drafts' => count(array_filter($myArticles, fn($a) => $a->status === 'draft')),
                'published' => count(array_filter($myArticles, fn($a) => $a->status === 'published')),
                'archived' => count(array_filter($myArticles, fn($a) => $a->status === 'archived')),
            ];
            
        }
        
        if ($this->isAdmin()) {
            $allUsers = $userRepository->findAll();
            $stats['users'] = [
                'total' => count($allUsers),
                'admins' => count(array_filter($allUsers, fn($u) => $u->role === 'admin')),
                'editors' => count(array_filter($allUsers, fn($u) => $u->role === 'editor')),
                'authors' => count(array_filter($allUsers, fn($u) => $u->role === 'author'))
            ];
        }

        // Statistiques catégories et tags (Admin/Editor)
        if ($this->canManageCategories()) {
            $stats['categories'] = count($categoryRepository->findAll());
            $stats['tags'] = count($tagRepository->findAll());
        }

        // Statistiques médias (tous les utilisateurs)
        $myMedia = $mediaRepository->findByUploader($currentUser->id);
        $stats['media'] = count($myMedia);

        // Articles récents (5 derniers)
        if ($this->canManageAllArticles()) {
            $allArticles = $articleRepository->findAll();
        } else {
            $allArticles = $articleRepository->findByAuthor($currentUser->id);
        }

        // Trier par date de création (plus récent en premier)
        usort($allArticles, function($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });
        $recentArticles = array_slice($allArticles, 0, 5);

         // Préparer les articles récents avec détails
        $recentArticlesData = [];
        foreach ($recentArticles as $article) {
            $author = $userRepository->find($article->author_id);
            $recentArticlesData[] = [
                'id' => $article->id,
                'title' => $article->title,
                'status' => $article->status,
                'author_name' => $author ? ($author->firstname . ' ' . $author->lastname) : 'Inconnu',
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at
            ];
        }
        

        $csrfToken = CsrfToken::generate();
        return $this->render('admin/dashboard', [
            'csrf_token' => $csrfToken,
            'stats' => $stats,
            'recent_articles' => $recentArticlesData,
            'current_user' => $currentUser,
            'is_admin' => $this->isAdmin(),
            'can_manage_all_articles' => $this->canManageAllArticles(),
            'can_manage_categories' => $this->canManageCategories(),
            'can_manage_users' => $this->canManageUsers()
        ]);
    }
}
