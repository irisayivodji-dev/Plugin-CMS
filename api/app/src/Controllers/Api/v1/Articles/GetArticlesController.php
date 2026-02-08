<?php

namespace App\Controllers\Api\v1\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleRepository;

class GetArticlesController extends AbstractController {
    public function process(Request $request): Response
    {
        $articleRepository = new ArticleRepository();
        // Par défaut, on ne retourne que les articles publiés
        $articles = $articleRepository->findByStatus('published');
        return new Response(json_encode([
            'success' => true,
            'articles' => $articles
        ]), 200, ['Content-Type' => 'application/json']);
    }
}

?>
