<?php

namespace App\Controllers\Api\v1\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\TagRepository;
use App\Repositories\MediaRepository;
use App\Entities\Article;

class CreateArticleController extends AbstractController {
    public function process(Request $request): Response
    {
        $data = json_decode($request->getPayload(), true);
        
        // Préparer les IDs de catégories et tags
        $categoryIds = [];
        if (!empty($data['category_id'])) {
            $categoryIds = is_array($data['category_id']) ? $data['category_id'] : [$data['category_id']];
        } elseif (!empty($data['categories']) && is_array($data['categories'])) {
            $categoryIds = $data['categories'];
        }
        
        $tagIds = [];
        if (!empty($data['tags']) && is_array($data['tags'])) {
            $tagIds = $data['tags'];
        }
        
        // Valider l'existence de toutes les catégories avant de créer l'article
        if (!empty($categoryIds)) {
            $categoryRepository = new CategoryRepository();
            foreach ($categoryIds as $catId) {
                $category = $categoryRepository->find($catId);
                if ($category === null) {
                    return new Response(
                        json_encode(['error' => "La catégorie avec l'ID $catId n'existe pas"]),
                        400,
                        ['Content-Type' => 'application/json']
                    );
                }
            }
        }
        
        // Valider l'existence de tous les tags avant de créer l'article
        if (!empty($tagIds)) {
            $tagRepository = new TagRepository();
            foreach ($tagIds as $tagId) {
                $tag = $tagRepository->find($tagId);
                if ($tag === null) {
                    return new Response(
                        json_encode(['error' => "Le tag avec l'ID $tagId n'existe pas"]),
                        400,
                        ['Content-Type' => 'application/json']
                    );
                }
            }
        }
        
        // Validation : contenu requis si status = 'published'
        $status = $data['status'] ?? 'draft';
        if ($status === 'published') {
            if (empty(trim($data['content'] ?? ''))) {
                return new Response(
                    json_encode(['error' => 'Le contenu est requis pour publier un article']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
        }
        
        // Validation : titre requis
        if (empty(trim($data['title'] ?? ''))) {
            return new Response(
                json_encode(['error' => 'Le titre est requis']),
                400,
                ['Content-Type' => 'application/json']
            );
        }
        
        // Créer l'article
        $articleRepository = new ArticleRepository();
        $article = new Article();
        $article->title = trim($data['title']);
        $article->content = $data['content'] ?? '';
        $article->excerpt = $data['excerpt'] ?? '';
        $article->author_id = $data['author_id'] ?? 1;
        $article->status = $status;
        $article->created_at = date('Y-m-d H:i:s');
        $article->updated_at = date('Y-m-d H:i:s');
        
        // Si publié, définir published_at
        if ($status === 'published' && !isset($data['published_at'])) {
            $article->published_at = date('Y-m-d H:i:s');
        }
        
        // Générer un slug unique
        $article->generateSlug();
        $article->slug = $articleRepository->generateUniqueSlug($article->slug);
        
        $article->id = $articleRepository->save($article);

        // Associer les catégories (déjà validées)
        if (!empty($categoryIds)) {
            foreach ($categoryIds as $catId) {
                $sql = "INSERT INTO article_category (article_id, category_id) VALUES (:article_id, :category_id)";
                $stmt = $articleRepository->getConnexion()->prepare($sql);
                $stmt->execute(['article_id' => $article->id, 'category_id' => $catId]);
            }
        }

        // Associer les tags (déjà validés)
        if (!empty($tagIds)) {
            foreach ($tagIds as $tagId) {
                $sql = "INSERT INTO article_tag (article_id, tag_id) VALUES (:article_id, :tag_id)";
                $stmt = $articleRepository->getConnexion()->prepare($sql);
                $stmt->execute(['article_id' => $article->id, 'tag_id' => $tagId]);
            }
        }

        // Associer les médias si fournis
        if (!empty($data['media_ids']) && is_array($data['media_ids'])) {
            $mediaRepository = new MediaRepository();
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
        }

        return new Response(json_encode([
            'success' => true,
            'message' => 'Article créé',
            'article' => $article
        ]), 201, ['Content-Type' => 'application/json']);
    }
}

?>
