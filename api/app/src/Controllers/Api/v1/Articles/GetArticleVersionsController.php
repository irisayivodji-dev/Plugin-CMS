<?php

namespace App\Controllers\Api\v1\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleVersionRepository;

class GetArticleVersionsController extends AbstractController {
    public function process(Request $request): Response
    {
        $articleId = (int)($request->getUrlParams()['id'] ?? 0);
        $repo = new ArticleVersionRepository();
        $versions = $repo->findByArticle($articleId);
        if (!$versions || count($versions) === 0) {
            return new Response(json_encode([]), 200, ['Content-Type' => 'application/json']);
        }
        return new Response(json_encode($versions), 200, ['Content-Type' => 'application/json']);
    }
}

?>
