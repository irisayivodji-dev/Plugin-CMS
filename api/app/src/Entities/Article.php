<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\ORM;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class Article extends AbstractEntity {
    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;
    
    #[Column(type: 'varchar', size: 255)]
    public string $title;
    
    #[Column(type: 'varchar', size: 255)]
    public string $slug;
    
    #[Column(type: 'text')]
    public ?string $content = null;
    
    #[Column(type: 'text')]
    public ?string $excerpt = null;
    
    #[Column(type: 'varchar', size: 50)]
    public string $status = 'draft';
    
    #[Column(type: 'int')]
    public int $author_id;
    
    #[Column(type: 'varchar', size: 255)]
    public string $created_at;
    
    #[Column(type: 'varchar', size: 255)]
    public string $updated_at;
    
    #[Column(type: 'varchar', size: 255)]
    public ?string $published_at = null;
    
    // Category
    protected array $categories = [];

    public function addCategory(Category $category): void
    {
        $this->categories[] = $category;
    }

    public function removeCategory(Category $category): void
    {
        $this->categories = array_filter(
            $this->categories,
            fn($c) => $c->id !== $category->id
        );
    }

    public function getCategories(): array
    {
        return $this->categories;
    }
    
    protected array $tags = [];

    public function addTag(Tag $tag): void
    {
        $this->tags[] = $tag;
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags = array_filter(
            $this->tags,
            fn($t) => $t->id !== $tag->id
        );
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function generateSlug(): void
    {
        $slug = strtolower($this->title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $this->slug = $slug;
    }
    
    public function getId(): int
    {
        return $this->id;
    }
}
