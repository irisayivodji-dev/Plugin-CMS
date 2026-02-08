<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\ORM;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class Media extends AbstractEntity {
    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;
    
    #[Column(type: 'varchar', size: 255)]
    public string $filename;
    
    #[Column(type: 'varchar', size: 500)]
    public string $file_path;
    
    #[Column(type: 'varchar', size: 50)]
    public string $file_type;  // 'image', 'video', 'document', 'audio'
    
    #[Column(type: 'varchar', size: 100)]
    public ?string $mime_type = null;
    
    #[Column(type: 'int')]
    public ?int $file_size = null;  // en bytes
    
    #[Column(type: 'text')]
    public ?string $alt_text = null;
    
    #[Column(type: 'varchar', size: 255)]
    public ?string $title = null;
    
    #[Column(type: 'text')]
    public ?string $description = null;
    
    #[Column(type: 'int')]
    public ?int $uploaded_by = null;
    
    #[Column(type: 'varchar', size: 255)]
    public string $created_at;
    
    #[Column(type: 'varchar', size: 255)]
    public string $updated_at;
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function determineFileType(string $mimeType): string
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'video';
        } elseif (strpos($mimeType, 'audio/') === 0) {
            return 'audio';
        } else {
            return 'document';
        }
    }
    
    public function getUrl(): string
    {
        return '/uploads/' . $this->file_path;
    }
    
    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }
    
    public function isVideo(): bool
    {
        return $this->file_type === 'video';
    }
}
