<?php

namespace App\Repositories;

use App\Entities\Media;
use App\Lib\Repositories\AbstractRepository;

class MediaRepository extends AbstractRepository
{
    public function getConnexion() {
        return $this->db->getConnexion();
    }
    
    public function getTable(): string {
        return 'media';
    }
    
    public function getOneResult() {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, Media::class);
        return $this->query->fetch();
    }

    public function getAllResults(): array {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, Media::class);
        return $this->query->fetchAll();
    }
    
    protected function hydrate(array $data): Media
    {
        $media = new Media();
        foreach ($data as $key => $value) {
            if (property_exists($media, $key)) {
                $media->$key = $value;
            }
        }
        return $media;
    }

    // Trouve tous les médias d'un type spécifique
    public function findByType(string $fileType): array
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE file_type = :file_type ORDER BY created_at DESC";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['file_type' => $fileType]);
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrate'], $results);
    }

    // Trouve tous les médias uploadés par un utilisateur
    public function findByUploader(int $userId): array
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE uploaded_by = :user_id ORDER BY created_at DESC";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrate'], $results);
    }

    // Trouve tous les médias d'un article
    public function findByArticle(int $articleId): array
    {
        $sql = "SELECT m.*, am.is_featured, am.display_order 
                FROM {$this->getTable()} m
                INNER JOIN article_media am ON m.id = am.media_id
                WHERE am.article_id = :article_id
                ORDER BY am.display_order ASC, m.created_at DESC";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrate'], $results);
    }

    // Trouve l'image à la une d'un article
    public function findFeaturedByArticle(int $articleId): ?Media
    {
        $sql = "SELECT m.* 
                FROM {$this->getTable()} m
                INNER JOIN article_media am ON m.id = am.media_id
                WHERE am.article_id = :article_id AND am.is_featured = TRUE
                LIMIT 1";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $this->hydrate($result) : null;
    }

    // Associe un média à un article
    public function attachToArticle(int $mediaId, int $articleId, bool $isFeatured = false, int $displayOrder = 0): void
    {
        $sql = "INSERT INTO article_media (article_id, media_id, is_featured, display_order)
                VALUES (:article_id, :media_id, :is_featured, :display_order)
                ON CONFLICT (article_id, media_id) DO UPDATE 
                SET is_featured = :is_featured, display_order = :display_order";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute([
            'article_id' => $articleId,
            'media_id' => $mediaId,
            'is_featured' => $isFeatured,
            'display_order' => $displayOrder
        ]);
    }

    // Détache un média d'un article
    public function detachFromArticle(int $mediaId, int $articleId): void
    {
        $sql = "DELETE FROM article_media WHERE article_id = :article_id AND media_id = :media_id";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute([
            'article_id' => $articleId,
            'media_id' => $mediaId
        ]);
    }

    // Détache tous les médias d'un article
    public function detachAllFromArticle(int $articleId): void
    {
        $sql = "DELETE FROM article_media WHERE article_id = :article_id";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
    }

    // Définit l'image à la une d'un article
    public function setFeatured(int $mediaId, int $articleId): void
    {
        // Retirer le statut featured de tous les médias de cet article
        $sql = "UPDATE article_media SET is_featured = FALSE WHERE article_id = :article_id";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);

        // Définir le nouveau média comme featured
        $sql = "UPDATE article_media SET is_featured = TRUE 
                WHERE article_id = :article_id AND media_id = :media_id";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute([
            'article_id' => $articleId,
            'media_id' => $mediaId
        ]);
    }

    // Met à jour l'ordre d'affichage des médias d'un article
    public function updateDisplayOrder(int $articleId, array $mediaIds): void
    {
        $conn = $this->db->getConnexion();
        $conn->beginTransaction();
        
        try {
            foreach ($mediaIds as $order => $mediaId) {
                $sql = "UPDATE article_media 
                        SET display_order = :display_order 
                        WHERE article_id = :article_id AND media_id = :media_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'article_id' => $articleId,
                    'media_id' => $mediaId,
                    'display_order' => $order
                ]);
            }
            
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
