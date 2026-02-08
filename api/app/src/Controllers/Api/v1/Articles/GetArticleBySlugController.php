<?php

namespace App\Controllers\Api\v1\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleRepository;

class GetArticleBySlugController extends AbstractController {
    public function process(Request $request): Response
    {
        $articleRepository = new ArticleRepository();
        
        // Récupérer le slug depuis les paramètres de route
        $slug = $request->getSlug('slug');
        
        if (empty($slug)) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Slug requis'
            ]), 400, ['Content-Type' => 'application/json']);
        }
        
        // Trouver l'article par slug
        $article = $articleRepository->findBySlug($slug);
        
        if (empty($article)) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Article non trouvé'
            ]), 404, ['Content-Type' => 'application/json']);
        }
        
        // Vérifier que l'article est publié (API publique)
        if ($article->status !== 'published') {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Article non disponible'
            ]), 404, ['Content-Type' => 'application/json']);
        }
        
        // Récupérer les catégories et tags associés
        $categories = $articleRepository->getCategories($article->id);
        $tags = $articleRepository->getTags($article->id);
        
        // Convertir l'article en tableau et ajouter les relations
        $articleData = $article->toArray();
        $articleData['categories'] = array_map(fn($cat) => $cat->toArray(), $categories);
        $articleData['tags'] = array_map(fn($tag) => $tag->toArray(), $tags);
        
        return new Response(json_encode([
            'success' => true,
            'article' => $articleData
        ]), 200, ['Content-Type' => 'application/json']);
    }
}

?>
