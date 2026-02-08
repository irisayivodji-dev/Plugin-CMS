<?php

namespace App\Lib\Entities;

use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\AutoIncrement;
use ReflectionClass;
use ReflectionProperty;

abstract class AbstractEntity {

    abstract public function getId(): int | string;
    
    public function toArray(): array {
        $array = [];
        $reflection = new ReflectionClass($this);
        
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $key = $property->getName();
            
            // N'inclure que les propriétés avec l'annotation #[Column]
            $attributes = $property->getAttributes(Column::class);
            if (!empty($attributes)) {
                // Vérifier si la propriété est initialisée avant d'accéder à sa valeur
                if ($property->isInitialized($this)) {
                    $array[$key] = $property->getValue($this);
                } else {
                    // Si la propriété n'est pas initialisée, vérifier si elle a AutoIncrement
                    $autoIncrementAttributes = $property->getAttributes(AutoIncrement::class);
                    // Exclure les propriétés AutoIncrement non initialisées (générées par la DB)
                    if (empty($autoIncrementAttributes)) {
                        // Pour les autres propriétés non initialisées, utiliser null
                        $array[$key] = null;
                    }
                    // Si c'est AutoIncrement et non initialisé, on ne l'inclut pas
                }
            }
        }
        
        return $array;
    }
}

?>