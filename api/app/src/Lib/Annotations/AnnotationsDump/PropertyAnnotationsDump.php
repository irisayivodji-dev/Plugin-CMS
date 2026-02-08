<?php

namespace App\Lib\Annotations\AnnotationsDump;

use App\Lib\Annotations\AbstractAnnotation;

class PropertyAnnotationsDump {
    private string $name;
    private array $annotations;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->annotations = [];
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
            throw new \Exception('annotation not found in property');
        }

        return $this->getAnnotations()[$annotation];
    }
}


?>
