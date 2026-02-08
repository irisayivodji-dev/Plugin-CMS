<?php

namespace App\Controllers\Api\v1\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleRepository;

class DeleteArticleController extends AbstractController {
    public function process(Request $request): Response
    {
        $articleRepository = new ArticleRepository();
        
        $article = $articleRepository->find($request->getSlug('id'));
        
        if(empty($article)) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Article non trouvé'
            ]), 404, ['Content-Type' => 'application/json']);
        }
        
        $articleRepository->remove($article);
        
        return new Response(json_encode([
            'success' => true,
            'message' => 'Article supprimé',
            'article_id' => $article->id
        ]), 200, ['Content-Type' => 'application/json']);
    }
}

?>
