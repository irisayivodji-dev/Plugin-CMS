<?php

namespace App\Controllers\Api\v1\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleRepository;
use App\Repositories\MediaRepository;

class PatchArticleController extends AbstractController {
    public function process(Request $request): Response
    {
        $payload = $request->getPayload();
        $contentType = $request->getHeaders()['Content-Type'] ?? null;
        $data = json_decode($payload, true);
        
        $articleRepository = new ArticleRepository();
        $article = $articleRepository->find($request->getSlug('id'));
        if(empty($article)) {
            return new Response(json_encode([
                'success' => false,
                'error' => 'Article non trouvé'
            ]), 404, ['Content-Type' => 'application/json']);
        }

        // Déterminer le statut final (nouveau ou actuel)
        $newStatus = $data['status'] ?? $article->status;
        
        // Validation : contenu requis si status = 'published'
        if ($newStatus === 'published') {
            $content = $data['content'] ?? $article->content;
            if (empty(trim($content ?? ''))) {
                return new Response(
                    json_encode(['error' => 'Le contenu est requis pour publier un article']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
        }
        
        if(isset($data['title'])) {
            $article->title = trim($data['title']);
            $article->generateSlug();
            // Générer un slug unique en excluant l'article actuel
            $article->slug = $articleRepository->generateUniqueSlug($article->slug, $article->id);
        }

        if(isset($data['content'])) {
            $article->content = $data['content'];
        }

        if(isset($data['excerpt'])) {
            $article->excerpt = $data['excerpt'];
        }
        
        if(isset($data['status'])) {
            $oldStatus = $article->status;
            $article->status = $data['status'];
            
            // Si on passe de draft à published, définir published_at
            if ($oldStatus !== 'published' && $data['status'] === 'published') {
                $article->published_at = date('Y-m-d H:i:s');
            }
        }

        $article->updated_at = date('Y-m-d H:i:s');
        $articleRepository->update($article);
        
        // Gérer les médias si fournis
        if (isset($data['media_ids'])) {
            $mediaRepository = new MediaRepository();
            
            if (is_array($data['media_ids']) && !empty($data['media_ids'])) {
                // Détacher tous les médias existants
                $mediaRepository->detachAllFromArticle($article->id);
                
                // Attacher les nouveaux médias
                $displayOrder = 0;
                foreach ($data['media_ids'] as $mediaId) {
                    $mediaId = (int) $mediaId;
                    if ($mediaId > 0) {
                        $isFeatured = (isset($data['featured_media_id']) && $data['featured_media_id'] == $mediaId);
                        $mediaRepository->attachToArticle($mediaId, $article->id, $isFeatured, $displayOrder);
                        $displayOrder++;
                    }
                }
                
                // Définir l'image à la une si spécifiée
                if (isset($data['featured_media_id']) && $data['featured_media_id'] > 0) {
                    $mediaRepository->setFeatured((int)$data['featured_media_id'], $article->id);
                }
            } else {
                // Si un tableau vide est fourni, détacher tous les médias
                $mediaRepository->detachAllFromArticle($article->id);
            }
        }
        
        return new Response(json_encode([
            'success' => true,
            'message' => 'Article mise à jour',
            'article' => $article
        ]), 200, ['Content-Type' => 'application/json']);
    }
}

?>
