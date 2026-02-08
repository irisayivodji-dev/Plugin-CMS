<?php

namespace App\Controllers\Api\v1\Categories;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\CategoryRepository;

class GetCategoryArticlesController extends AbstractController {
    public function process(Request $request): Response
    {
        $categoryRepository = new CategoryRepository();
        $categoryId = $request->getSlug('id');
        $category = $categoryRepository->find($categoryId);
        if(empty($category)) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Catégorie non trouvée'
            ]), 404, ['Content-Type' => 'application/json']);
        }
        $articles = $categoryRepository->getArticles($categoryId);
        return new Response(json_encode([
            'success' => true,
            'category' => $category,
            'articles' => $articles
        ]), 200, ['Content-Type' => 'application/json']);
    }
}
