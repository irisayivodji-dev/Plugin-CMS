<?php

namespace App\Repositories;
use App\Lib\Entities\AbstractEntity;
use App\Repositories\ArticleVersionRepository;
use App\Lib\Repositories\AbstractRepository;
use App\Entities\Article;

class ArticleRepository extends AbstractRepository {

    public function getConnexion() {
        return $this->db->getConnexion();
    }
    
    public function getTable(): string {
        return 'articles';
    }
    
    public function getOneResult() {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, Article::class);
        return $this->query->fetch();
    }

    public function getAllResults(): array {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, Article::class);
        return $this->query->fetchAll();
    }
    
    public function findBySlug(string $slug): ?Article
    {
        return $this->findOneBy(['slug' => $slug]);
    }
    
    public function findByAuthor(int $authorId): array
    {
        return $this->findBy(['author_id' => $authorId]);
    }
    
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    //Génère un slug unique en vérifiant s'il existe déjà dans la base de données
    //Si le slug existe, ajoute un suffixe numérique (ex: premier-article-2)

    public function generateUniqueSlug(string $baseSlug, ?int $excludeArticleId = null): string
    {
        $slug = $baseSlug;
        $counter = 1;
        
        // Vérifier si le slug existe déjà (en excluant l'article actuel si on est en mode update)
        while (true) {
            $existingArticle = $this->findBySlug($slug);
            if ($existingArticle === null) {
                // Le slug est disponible
                break;
            }
            // Si on est en mode update et que l'article trouvé est celui qu'on modifie, on peut utiliser ce slug
            if ($excludeArticleId !== null && $existingArticle->id === $excludeArticleId) {
                break;
            }
            // Sinon, générer un nouveau slug avec un suffixe
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    //les catégories associées à un article

    public function getCategories(int $articleId): array
    {
        $sql = "SELECT c.* FROM category c INNER JOIN article_category ac ON c.id = ac.category_id WHERE ac.article_id = :article_id";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, \App\Entities\Category::class);
    }

    //les tags associées à un article

    public function getTags(int $articleId): array
    {
        $sql = "SELECT t.* FROM tags t INNER JOIN article_tag at ON t.id = at.tag_id WHERE at.article_id = :article_id";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, \App\Entities\Tag::class);
    }
    
    //Compte le nombre d'articles créés par un auteur
    public function countByAuthor(int $authorId): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->getTable()} WHERE author_id = :author_id";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['author_id' => $authorId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    //Compte le nombre d'articles créés par categorie
    public function countByCategory(int $categoryId): int
    {
        $sql = "SELECT COUNT(DISTINCT article_id) as count FROM article_category WHERE category_id = :category_id";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['category_id' => $categoryId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }
    
    // Sauvegarde les catégories d'un article
    public function saveCategories(int $articleId, array $categoryIds): void
    {
        $conn = $this->db->getConnexion();
        
        // Supprimer les anciennes relations
        $sql = "DELETE FROM article_category WHERE article_id = :article_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
        
        // Ajouter les nouvelles relations
        if (!empty($categoryIds)) {
            $sql = "INSERT INTO article_category (article_id, category_id) VALUES (:article_id, :category_id)";
            $stmt = $conn->prepare($sql);
            
            foreach ($categoryIds as $categoryId) {
                $categoryId = (int) $categoryId;
                if ($categoryId > 0) {
                    $stmt->execute([
                        'article_id' => $articleId,
                        'category_id' => $categoryId
                    ]);
                }
            }
        }
    }
    
    // Sauvegarde les tags d'un article
    public function saveTags(int $articleId, array $tagIds): void
    {
        $conn = $this->db->getConnexion();
        
        // Supprimer les anciennes relations
        $sql = "DELETE FROM article_tag WHERE article_id = :article_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
        
        // Ajouter les nouvelles relations
        if (!empty($tagIds)) {
            $sql = "INSERT INTO article_tag (article_id, tag_id) VALUES (:article_id, :tag_id)";
            $stmt = $conn->prepare($sql);
            
            foreach ($tagIds as $tagId) {
                $tagId = (int) $tagId;
                if ($tagId > 0) {
                    $stmt->execute([
                        'article_id' => $articleId,
                        'tag_id' => $tagId
                    ]);
                }
            }
        }
    }
    
    // Récupère tous les médias d'un article
    public function getMedia(int $articleId): array
    {
        $mediaRepository = new MediaRepository();
        return $mediaRepository->findByArticle($articleId);
    }

    // Récupère l'image à la une d'un article
    public function getFeaturedMedia(int $articleId): ?\App\Entities\Media
    {
        $mediaRepository = new MediaRepository();
        return $mediaRepository->findFeaturedByArticle($articleId);
    }
    
    public function update(AbstractEntity $entity) {
        // Appele la méthode parente qui gère correctement les paramètres
        parent::update($entity);
        
        // Sauvegarde de l'ancienne version après la mise à jour
        if ($entity instanceof \App\Entities\Article) {
            $versionRepo = new \App\Repositories\ArticleVersionRepository();
            $versionRepo->saveVersionFromArticle($entity);
        }
    }
}
?>
