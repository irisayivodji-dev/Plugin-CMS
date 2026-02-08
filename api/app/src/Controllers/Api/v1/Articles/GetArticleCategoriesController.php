<?php

namespace App\Controllers\Api\v1\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleRepository;

class GetArticleCategoriesController extends AbstractController {
    public function process(Request $request): Response
    {
        $articleRepository = new ArticleRepository();
        $articleId = $request->getSlug('id');
        $categories = $articleRepository->getCategories($articleId);
        if ($categories === false || $categories === null) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Aucune catégorie trouvée ou article inexistant'
            ]), 404, ['Content-Type' => 'application/json']);
        }
        return new Response(json_encode([
            'success' => true,
            'categories' => $categories
        ]), 200, ['Content-Type' => 'application/json']);
    }
}
