<?php

namespace App\Lib\Annotations\AnnotationsDump;

use App\Lib\Annotations\AbstractAnnotation;

class ClassAnnotationsDump {
    public string $name;
    private array $annotations;
    private array $properties;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->annotations = [];
        $this->properties = [];
    }

    public function getName(): string {
        return $this->name;
    }

    public function addAnnotation(string $annotationName, AbstractAnnotation $annotation): void {
        $this->annotations[$annotationName] = $annotation;
    }

    public function getAnnotations(): array {
        return $this->annotations;
    }

    public function hasAnnotation(string $annotation): bool {
        return array_key_exists($annotation, $this->getAnnotations());
    }

    public function getAnnotation(string $annotation): AbstractAnnotation {
        if($this->hasAnnotation($annotation) === false) {
            throw new \Exception('annotation not found in class');
        }

        return $this->getAnnotations()[$annotation];
    }
    
    public function addProperty(PropertyAnnotationsDump $property): void {
        $this->properties[] = $property;
    }

    public function getProperties(): array {
        return $this->properties;
    }

    public function propertiesHaveAnnotation(string $annotation): bool {
        foreach($this->getProperties() as $property) {
            if($property->hasAnnotation($annotation) === true) {
                return true;
            }
        }
        
        return false;
    }

    public function getPropertiesWithAnnotation(string $annotation): array {
        $properties = [];

        foreach($this->getProperties() as $property) {
            if($property->hasAnnotation($annotation) === true) {
                $properties[] = $property;
            }
        }

        return $properties;
    }
}

?>
