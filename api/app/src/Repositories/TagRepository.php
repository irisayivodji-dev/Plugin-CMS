<?php

namespace App\Repositories;

use App\Lib\Repositories\AbstractRepository;
use App\Entities\Tag;

class TagRepository extends AbstractRepository {
    
    public function getTable(): string {
        return 'tags';
    }
    
    public function getOneResult() {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, Tag::class);
        return $this->query->fetch();
    }

    public function getAllResults(): array {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, Tag::class);
        return $this->query->fetchAll();
    }
    
    public function findBySlug(string $slug): ?Tag
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findByName(string $name): ?Tag
    {
        // Recherche insensible à la casse
        $sql = "SELECT * FROM {$this->getTable()} WHERE LOWER(name) = LOWER(:name) LIMIT 1";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['name' => $name]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        $tag = new Tag();
        foreach ($result as $key => $value) {
            if (property_exists($tag, $key)) {
                $tag->$key = $value;
            }
        }
        
        return $tag;
    }
    
    public function create(Tag $tag): bool
    {
        $sql = "INSERT INTO tags (name, slug, description, created_at, updated_at) VALUES (:name, :slug, :description, :created_at, :updated_at)";
        $stmt = $this->db->getConnexion()->prepare($sql);
        return $stmt->execute([
            'name' => $tag->name,
            'slug' => $tag->slug,
            'description' => $tag->description,
            'created_at' => $tag->created_at,
            'updated_at' => $tag->updated_at
        ]);
    }
    
        
     //les articles associés à un tag
    public function getArticles(int $tagId): array
    {
        $sql = "SELECT a.* FROM articles a INNER JOIN article_tag at ON a.id = at.article_id WHERE at.tag_id = :tag_id";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['tag_id' => $tagId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, \App\Entities\Article::class);
    }
}
?>
