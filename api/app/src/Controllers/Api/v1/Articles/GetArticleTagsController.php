<?php

namespace App\Controllers\Api\v1\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleRepository;

class GetArticleTagsController extends AbstractController {
    public function process(Request $request): Response
    {
        $articleRepository = new ArticleRepository();
        $articleId = $request->getSlug('id');
        $tags = $articleRepository->getTags($articleId);
        if ($tags === false || $tags === null) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Aucun tag trouvé ou article inexistant'
            ]), 404, ['Content-Type' => 'application/json']);
        }
        return new Response(json_encode([
            'success' => true,
            'tags' => $tags
        ]), 200, ['Content-Type' => 'application/json']);
    }
}
