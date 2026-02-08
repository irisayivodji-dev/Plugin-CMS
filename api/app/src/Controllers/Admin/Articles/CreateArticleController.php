<?php

namespace App\Controllers\Admin\Articles;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Lib\Auth\CsrfToken;
use App\Entities\Article;
use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\TagRepository;
use App\Repositories\MediaRepository;

class CreateArticleController extends AbstractController
{
    public function process(Request $request): Response
    {
        $this->request = $request;
        // Tous les utilisateurs authentifiés peuvent créer des articles
        $this->requireCanCreateArticles();

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        // Afficher le formulaire
        $categoryRepository = new CategoryRepository();
        $tagRepository = new TagRepository();
        $mediaRepository = new MediaRepository();
        
        // Récupérer les médias de l'utilisateur connecté
        $authService = new \App\Lib\Auth\AuthService();
        $currentUser = $authService->getCurrentUser();
        $media = [];
        if ($currentUser) {
            $media = $mediaRepository->findByUploader($currentUser->id);
        }
        
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/articles/create', [
            'csrf_token' => $csrfToken,
            'categories' => $categoryRepository->findAll(),
            'tags' => $tagRepository->findAll(),
            'media' => $media,
            'errors' => [],
            'old' => [],
            'canPublish' => $this->canPublishArticles()
        ]);
    }

    private function handlePost(Request $request): Response
    {
        $csrfToken = $request->post('csrf_token');
        if (!CsrfToken::validate($csrfToken ?? '')) {
            return $this->renderWithErrors(['csrf' => 'Token CSRF invalide'], $request->getPost());
        }

        $data = $request->getPost();
        $errors = $this->validate($data);

        if (!empty($errors)) {
            return $this->renderWithErrors($errors, $data);
        }

        $articleRepository = new ArticleRepository();
        $authService = new \App\Lib\Auth\AuthService();
        $currentUser = $authService->getCurrentUser();

        if (!$currentUser) {
            return Response::redirect('/login');
        }

        // Créer l'article
        $article = new Article();
        $article->title = trim($data['title']);
        $article->content = trim($data['content'] ?? '');
        $article->excerpt = trim($data['excerpt'] ?? '');
        $article->author_id = $currentUser->id;
        $article->created_at = date('Y-m-d H:i:s');
        $article->updated_at = date('Y-m-d H:i:s');
        
        // Tous les articles sont créés en brouillon par défaut
        // La publication se fait ensuite via le bouton "Publier" dans la liste
        $article->status = 'draft';
        
        // Générer le slug
        $article->generateSlug();
        $article->slug = $articleRepository->generateUniqueSlug($article->slug);
        
        try {
            $article->id = $articleRepository->save($article);
            
            // Gérer les catégories si fournies
            if (isset($data['categories']) && is_array($data['categories'])) {
                $articleRepository->saveCategories($article->id, $data['categories']);
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
            
            // Associer les tags à l'article
            if (!empty($tagIds)) {
                $articleRepository->saveTags($article->id, $tagIds);
            }
            
            // Gérer les médias si fournis
            $mediaRepository = new MediaRepository();
            
            // Si un featured_media_id est fourni, l'ajouter à media_ids s'il n'y est pas déjà
            if (isset($data['featured_media_id']) && $data['featured_media_id'] > 0) {
                $featuredMediaId = (int)$data['featured_media_id'];
                if (!isset($data['media_ids']) || !is_array($data['media_ids'])) {
                    $data['media_ids'] = [];
                }
                // Ajouter le featured_media_id à media_ids s'il n'y est pas déjà
                if (!in_array($featuredMediaId, $data['media_ids'])) {
                    $data['media_ids'][] = $featuredMediaId;
                }
            }
            
            if (isset($data['media_ids']) && is_array($data['media_ids']) && !empty($data['media_ids'])) {
                $displayOrder = 0;
                $isFirst = true;
                
                foreach ($data['media_ids'] as $mediaId) {
                    $mediaId = (int) $mediaId;
                    if ($mediaId > 0) {
                        // Le premier média peut être défini comme featured si spécifié
                        $isFeatured = $isFirst && (isset($data['featured_media_id']) && $data['featured_media_id'] == $mediaId);
                        $mediaRepository->attachToArticle($mediaId, $article->id, $isFeatured, $displayOrder);
                        $displayOrder++;
                        $isFirst = false;
                    }
                }
                
                // Si un featured_media_id est spécifié séparément, le définir
                if (isset($data['featured_media_id']) && $data['featured_media_id'] > 0) {
                    $mediaRepository->setFeatured((int)$data['featured_media_id'], $article->id);
                }
            }
            
        } catch (\PDOException $e) {
            return $this->renderWithErrors(['title' => 'Une erreur est survenue lors de la création. Veuillez réessayer.'], $data);
        }

        // Message de succès
        \App\Lib\Auth\Session::set('flash_success', 'Article créé avec succès');

        return Response::redirect('/admin/articles');
    }

    private function validate(array $data): array
    {
        $errors = [];

        // Titre
        if (empty(trim($data['title'] ?? ''))) {
            $errors['title'] = 'Le titre est requis';
        } elseif (strlen(trim($data['title'])) > 255) {
            $errors['title'] = 'Le titre ne doit pas dépasser 255 caractères';
        }

        // Le contenu est optionnel à la création (toujours en brouillon)
        // La validation du contenu se fera lors de la publication via le bouton "Publier"

        return $errors;
    }

    private function renderWithErrors(array $errors, array $old): Response
    {
        $categoryRepository = new CategoryRepository();
        $tagRepository = new TagRepository();
        
        $csrfToken = CsrfToken::generate();
        return $this->render('admin/articles/create', [
            'csrf_token' => $csrfToken,
            'categories' => $categoryRepository->findAll(),
            'tags' => $tagRepository->findAll(),
            'errors' => $errors,
            'old' => $old,
            'canPublish' => $this->canPublishArticles()
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
