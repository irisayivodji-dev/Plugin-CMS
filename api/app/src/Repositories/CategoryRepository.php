<?php

namespace App\Repositories;

use App\Lib\Repositories\AbstractRepository;
use App\Entities\Category;

class CategoryRepository extends AbstractRepository {
    
    public function getTable(): string {
        return 'category';
    }
    
    public function getOneResult() {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, Category::class);
        return $this->query->fetch();
    }

    public function getAllResults(): array {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, Category::class);
        return $this->query->fetchAll();
    }
    
    public function findBySlug(string $slug): ?Category
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findByName(string $name): ?Category
    {
        return $this->findOneBy(['name' => $name]);
    }
    
    public function create(Category $category): bool
    {
        $sql = "INSERT INTO category (name, slug, description, created_at, updated_at) VALUES (:name, :slug, :description, :created_at, :updated_at)";
        $stmt = $this->db->getConnexion()->prepare($sql);
        return $stmt->execute([
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at
        ]);
    }
    
        
     //les articles associés à une catégorie
    public function getArticles(int $categoryId): array
    {
        $sql = "SELECT a.* FROM articles a INNER JOIN article_category ac ON a.id = ac.article_id WHERE ac.category_id = :category_id";
        $stmt = $this->db->getConnexion()->prepare($sql);
        $stmt->execute(['category_id' => $categoryId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, \App\Entities\Article::class);
    }
}
?>
