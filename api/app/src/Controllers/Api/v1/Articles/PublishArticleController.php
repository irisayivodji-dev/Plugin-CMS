<?php

namespace App\Controllers\Api\v1\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleRepository;
use App\Lib\Http\Middleware\RoleMiddleware;

class PublishArticleController extends AbstractController {
    public function process(Request $request): Response
    {
        // Restriction : seuls admin et editor peuvent publier
        $role = \App\Lib\Auth\Session::get('user_role');
        if ($role !== 'admin' && $role !== 'editor') {
            return new Response(json_encode(['error' => 'Accès réservé aux éditeurs ou admins']), 403, ['Content-Type' => 'application/json']);
        }
        $articleRepository = new ArticleRepository();
        $article = $articleRepository->find($request->getSlug('id'));
        if(empty($article)) {
            return new Response(json_encode(['error' => 'not found']), 404, ['Content-Type' => 'application/json']);
        }
        // On ne publie que si pas déjà publié ou archivé
        if($article->status === 'published') {
            return new Response(json_encode(['error' => 'déjà publié']), 400, ['Content-Type' => 'application/json']);
        }
        if($article->status === 'archived') {
            return new Response(json_encode(['error' => 'archivé']), 400, ['Content-Type' => 'application/json']);
        }
        $article->status = 'published';
        $article->published_at = date('Y-m-d H:i:s');
        $article->updated_at = date('Y-m-d H:i:s');
        $articleRepository->update($article);
        return new Response(json_encode($article), 200, ['Content-Type' => 'application/json']);
    }
}
