<?php

namespace App\Controllers\Api\v1\Tags;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\TagRepository;

class GetTagArticlesController extends AbstractController {
    public function process(Request $request): Response
    {
        $tagRepository = new TagRepository();
        $tagId = $request->getSlug('id');
        $tag = $tagRepository->find($tagId);
        if(empty($tag)) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Tag non trouvé'
            ]), 404, ['Content-Type' => 'application/json']);
        }
        $articles = $tagRepository->getArticles($tagId);
        return new Response(json_encode([
            'success' => true,
            'tag' => $tag,
            'articles' => $articles
        ]), 200, ['Content-Type' => 'application/json']);
    }
}
