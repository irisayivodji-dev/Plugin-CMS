<?php
namespace App\Repositories;

use App\Lib\Repositories\AbstractRepository;
use App\Entities\ArticleVersion;

class ArticleVersionRepository extends AbstractRepository {
    public function getTable(): string {
        return 'article_versions';
    }

    public function getOneResult() {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, ArticleVersion::class);
        return $this->query->fetch();
    }

    public function getAllResults(): array {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, ArticleVersion::class);
        return $this->query->fetchAll();
    }

    public function findByArticle(int $articleId): array
    {
        return $this->findBy(['article_id' => $articleId]);
    }

    public function saveVersionFromArticle(\App\Entities\Article $article): void
    {
        $version = new ArticleVersion();
        $version->article_id = $article->id;
        $version->title = $article->title;
        $version->content = $article->content;
        $version->author_id = $article->author_id;
        $version->created_at = $article->created_at;
        $version->updated_at = $article->updated_at;
        $this->save($version);
    }
}
