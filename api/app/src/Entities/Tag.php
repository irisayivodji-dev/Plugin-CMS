<?php
   namespace App\Entities;

    use App\Lib\Annotations\ORM\ORM;
    use App\Lib\Annotations\ORM\Column;
    use App\Lib\Annotations\ORM\Id;
    use App\Lib\Annotations\ORM\AutoIncrement;
    use App\Lib\Entities\AbstractEntity;

    #[ORM]
    class Tag extends AbstractEntity {
        #[Id]
        #[AutoIncrement]
        #[Column(type: 'int')]
        public int $id;

        #[Column(type: 'varchar', size: 255)]
        public string $name;

        #[Column(type: 'varchar', size: 255)]
        public string $slug;

        #[Column(type: 'text')]
        public ?string $description = null;

        #[Column(type: 'varchar', size: 255)]
        public string $created_at;

        #[Column(type: 'varchar', size: 255)]
        public string $updated_at;

        public function getId(): int
        {
            return $this->id;
        }
        public function generateSlug(): void
        {
            $slug = $this->name;
            
            // Convertir en minuscules
            $slug = mb_strtolower($slug, 'UTF-8');
            
            // Remplacer les accents
            $slug = $this->removeAccents($slug);
            
            // Remplacer les espaces et caractères spéciaux par des tirets
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            
            // Supprimer les tirets en début et fin
            $slug = trim($slug, '-');
            
            $this->slug = $slug;
        }
        
        private function removeAccents(string $string): string
        {
            // Table de correspondance des accents
            $accents = [
                'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
                'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
                'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
                'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
                'ý' => 'y', 'ÿ' => 'y',
                'ç' => 'c',
                'ñ' => 'n',
                'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a', 'Å' => 'a',
                'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e',
                'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i',
                'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
                'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u',
                'Ý' => 'y',
                'Ç' => 'c',
                'Ñ' => 'n'
            ];
            
            return strtr($string, $accents);
        }
    }
?>