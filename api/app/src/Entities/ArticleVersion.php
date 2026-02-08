<?php
namespace App\Entities;

use App\Lib\Annotations\ORM\ORM;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class ArticleVersion extends AbstractEntity {
    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;

    #[Column(type: 'int')]
    public int $article_id;

    #[Column(type: 'varchar', size: 255)]
    public string $title;

    #[Column(type: 'text')]
    public string $content;

    #[Column(type: 'int')]
    public ?int $author_id = null;

    #[Column(type: 'varchar', size: 255)]
    public string $created_at;

    #[Column(type: 'varchar', size: 255)]
    public string $updated_at;

    public function getId(): int {
        return $this->id;
    }
}
