<?php

namespace App\Controllers\Admin\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\TagRepository;
use App\Repositories\MediaRepository;

class EditArticleController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        
        $articleId = (int) $request->getSlug('id');
        $articleRepository = new ArticleRepository();
        $article = $articleRepository->find($articleId);

        if (empty($article)) {
            \App\Lib\Auth\Session::set('flash_error', 'Article non trouvé');
            return Response::redirect('/admin/articles');
        }

        // Vérifier les permissions : admin/editor peuvent éditer tous, author uniquement les siens
        $this->requireCanManageArticle($article);

        // Vérifier si c'est une requête PATCH (via _method) ou POST
        $postData = $request->getPost();
        $isPatch = $request->getMethod() === 'PATCH' || 
                   ($request->getMethod() === 'POST' && isset($postData['_method']) && strtoupper($postData['_method']) === 'PATCH');
        
        if ($isPatch) {
            return $this->handlePatch($request, $article, $articleRepository);
        }

        // GET - Afficher le formulaire
        $categoryRepository = new CategoryRepository();
        $tagRepository = new TagRepository();
        
        // Récupérer les catégories, tags et médias actuels de l'article
        $articleCategories = $articleRepository->getCategories($article->id);
        $articleTags = $articleRepository->getTags($article->id);
        $mediaRepository = new MediaRepository();
        $articleMedia = $mediaRepository->findByArticle($article->id);
        
        // Extraire les IDs pour pré-sélectionner dans le formulaire
        $selectedCategoryIds = array_map(fn($cat) => $cat->id, $articleCategories);
        $selectedTagIds = array_map(fn($tag) => $tag->id, $articleTags);
        $selectedTagNames = array_map(fn($tag) => $tag->name, $articleTags); // Pour le champ texte libre
        $selectedMediaIds = array_map(fn($media) => $media->id, $articleMedia);
        $featuredMediaId = null;
        foreach ($articleMedia as $media) {
            if (isset($media->is_featured) && $media->is_featured) {
                $featuredMediaId = $media->id;
                break;
            }
        }
        
        // Récupérer les médias de l'utilisateur connecté
        $authService = new \App\Lib\Auth\AuthService();
        $currentUser = $authService->getCurrentUser();
        $media = [];
        if ($currentUser) {
            $media = $mediaRepository->findByUploader($currentUser->id);
        }
        
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/articles/edit', [
            'csrf_token' => $csrfToken,
            'article' => $article,
            'categories' => $categoryRepository->findAll(),
            'tags' => $tagRepository->findAll(),
            'selectedCategoryIds' => $selectedCategoryIds,
            'selectedTagIds' => $selectedTagIds,
            'selectedTagNames' => $selectedTagNames,
            'selectedMediaIds' => $selectedMediaIds,
            'featuredMediaId' => $featuredMediaId,
            'media' => $media,
            'errors' => [],
            'old' => [],
            'canPublish' => $this->canPublishArticles(),
            'canManageAll' => $this->canManageAllArticles()
        ]);
    }

    private function handlePatch(Request $request, $article, ArticleRepository $articleRepository): Response
    {
        $csrfToken = $request->post('csrf_token');
        if (!CsrfToken::validate($csrfToken ?? '')) {
            return $this->renderWithErrors(['csrf' => 'Token CSRF invalide'], $request->getPost(), $article);
        }

        $data = $request->getPost();
        $errors = $this->validate($data, $article);

        if (!empty($errors)) {
            return $this->renderWithErrors($errors, $data, $article);
        }

        // Mettre à jour l'article
        if (isset($data['title'])) {
            $article->title = trim($data['title']);
            $article->generateSlug();
            $article->slug = $articleRepository->generateUniqueSlug($article->slug, $article->id);
        }
        
        if (isset($data['content'])) {
            $article->content = trim($data['content']);
        }
        
        if (isset($data['excerpt'])) {
            $article->excerpt = trim($data['excerpt']);
        }
        
        // Gestion du statut : seuls admin/editor peuvent publier
        if ($this->canPublishArticles() && isset($data['status'])) {
            $oldStatus = $article->status;
            $article->status = $data['status'];
            
            // Si on passe de draft à published, définir published_at
            if ($oldStatus !== 'published' && $data['status'] === 'published') {
                $article->published_at = date('Y-m-d H:i:s');
            }
        }
        
        $article->updated_at = date('Y-m-d H:i:s');

        try {
            $articleRepository->update($article);
            
            // Gérer les catégories si fournies
            if (isset($data['categories']) && is_array($data['categories'])) {
                $articleRepository->saveCategories($article->id, $data['categories']);
            } else {
                // Si aucune catégorie n'est fournie, supprimer toutes les relations
                $articleRepository->saveCategories($article->id, []);
            }
            
            // Gérer les tags depuis le champ texte libre
            $tagRepository = new TagRepository();
            $tagIds = [];
            
            if (!empty($data['tags_input'] ?? '')) {
                $tagNames = $this->parseTagInput($data['tags_input']);
                
                foreach ($tagNames as $tagName) {
                    $tagName = trim($tagName);
                    if (empty($tagName)) {
                        continue;
                    }
                    
                    // Chercher si le tag existe déjà
                    $tag = $tagRepository->findByName($tagName);
                    
                    if (!$tag) {
                        // Créer le tag s'il n'existe pas
                        $tag = new \App\Entities\Tag();
                        $tag->name = $tagName;
                        $tag->generateSlug();
                        
                        // Vérifier l'unicité du slug
                        $slug = $tag->slug;
                        $counter = 1;
                        while ($tagRepository->findBySlug($slug)) {
                            $slug = $tag->slug . '-' . $counter;
                            $counter++;
                        }
                        $tag->slug = $slug;
                        
                        $tag->created_at = date('Y-m-d H:i:s');
                        $tag->updated_at = date('Y-m-d H:i:s');
                        
                        $tag->id = $tagRepository->save($tag);
                    }
                    
                    $tagIds[] = $tag->id;
                }
            }
            
            // Associer les tags à l'article (remplace les anciens)
            $articleRepository->saveTags($article->id, $tagIds);
            
            // Gérer les médias si fournis
            $mediaRepository = new MediaRepository();
            if (isset($data['media_ids']) && is_array($data['media_ids']) && !empty($data['media_ids'])) {
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
            } elseif (isset($data['media_ids']) && empty($data['media_ids'])) {
                // Si un tableau vide est fourni, détacher tous les médias
                $mediaRepository->detachAllFromArticle($article->id);
            }
            
        } catch (\PDOException $e) {
            return $this->renderWithErrors(['title' => 'Une erreur est survenue lors de la modification. Veuillez réessayer.'], $data, $article);
        }

        // Message de succès
        \App\Lib\Auth\Session::set('flash_success', 'Article modifié avec succès');

        return Response::redirect('/admin/articles');
    }

    private function validate(array $data, $article): array
    {
        $errors = [];

        // Titre
        if (isset($data['title'])) {
            if (empty(trim($data['title']))) {
                $errors['title'] = 'Le titre est requis';
            } elseif (strlen(trim($data['title'])) > 255) {
                $errors['title'] = 'Le titre ne doit pas dépasser 255 caractères';
            }
        }

        // Déterminer le statut final (nouveau ou actuel)
        $newStatus = $data['status'] ?? $article->status;
        
        // Statut (vérifier que l'utilisateur peut publier)
        if ($newStatus === 'published') {
            if (!$this->canPublishArticles()) {
                $errors['status'] = 'Vous n\'avez pas la permission de publier des articles';
            }
            
            // Contenu requis uniquement si l'article est publié
            $content = $data['content'] ?? $article->content;
            if (empty(trim($content ?? ''))) {
                $errors['content'] = 'Le contenu est requis pour publier un article';
            }
        }
        // Si c'est un brouillon, le contenu est optionnel

        return $errors;
    }

    private function renderWithErrors(array $errors, array $old, $article): Response
    {
        $categoryRepository = new CategoryRepository();
        $tagRepository = new TagRepository();
        $articleRepository = new ArticleRepository();
        
        // Récupérer les catégories et tags actuels ou depuis les données soumises
        if (isset($old['categories']) && is_array($old['categories'])) {
            $selectedCategoryIds = array_map('intval', $old['categories']);
        } else {
            $articleCategories = $articleRepository->getCategories($article->id);
            $selectedCategoryIds = array_map(fn($cat) => $cat->id, $articleCategories);
        }
        
        // Gérer les tags pour le champ texte libre
        if (isset($old['tags_input']) && !empty($old['tags_input'])) {
            $selectedTagNames = explode(',', $old['tags_input']);
            $selectedTagNames = array_map('trim', $selectedTagNames);
            $selectedTagIds = [];
        } else {
            $articleTags = $articleRepository->getTags($article->id);
            $selectedTagIds = array_map(fn($tag) => $tag->id, $articleTags);
            $selectedTagNames = array_map(fn($tag) => $tag->name, $articleTags);
        }
        
        $mediaRepository = new MediaRepository();
        if (isset($old['media_ids']) && is_array($old['media_ids'])) {
            $selectedMediaIds = array_map('intval', $old['media_ids']);
            $featuredMediaId = isset($old['featured_media_id']) ? (int)$old['featured_media_id'] : null;
        } else {
            $articleMedia = $mediaRepository->findByArticle($article->id);
            $selectedMediaIds = array_map(fn($media) => $media->id, $articleMedia);
            $featuredMediaId = null;
            foreach ($articleMedia as $media) {
                if (isset($media->is_featured) && $media->is_featured) {
                    $featuredMediaId = $media->id;
                    break;
                }
            }
        }
        
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/articles/edit', [
            'csrf_token' => $csrfToken,
            'article' => $article,
            'categories' => $categoryRepository->findAll(),
            'tags' => $tagRepository->findAll(),
            'selectedCategoryIds' => $selectedCategoryIds,
            'selectedTagIds' => $selectedTagIds ?? [],
            'selectedTagNames' => $selectedTagNames ?? [],
            'selectedMediaIds' => $selectedMediaIds,
            'featuredMediaId' => $featuredMediaId,
            'errors' => $errors,
            'old' => $old,
            'canPublish' => $this->canPublishArticles(),
            'canManageAll' => $this->canManageAllArticles()
        ]);
    }

    /**
     * Parse le champ de tags (séparés par virgules) et retourne un tableau de noms de tags
     */
    private function parseTagInput(string $input): array
    {
        // Séparer par virgules
        $tags = explode(',', $input);
        
        // Nettoyer chaque tag (trim, supprimer les doublons)
        $cleanedTags = [];
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                // Normaliser en minuscules pour éviter les doublons (PHP vs php)
                $normalized = mb_strtolower($tag, 'UTF-8');
                $normalizedExisting = array_map('mb_strtolower', $cleanedTags);
                if (!in_array($normalized, $normalizedExisting)) {
                    $cleanedTags[] = $tag;
                }
            }
        }
        
        return $cleanedTags;
    }
}

?>
