<?php

namespace App\Lib\Annotations;

use App\Lib\Annotations\AnnotationsDump\PropertyAnnotationsDump;
use App\Lib\Annotations\AnnotationsDump\ClassAnnotationsDump;

class AnnotationReader {

    public static function extractFromClass(string $className): ClassAnnotationsDump {
        if(class_exists($className) === false) {
            throw new \Exception('Not a class');
        }

        $classAnnotationsDump = new ClassAnnotationsDump($className);
        $reflectionClass = new \ReflectionClass($className);
        foreach($reflectionClass->getAttributes() as $attribute) {
            $classAnnotationsDump->addAnnotation($attribute->getName(), $attribute->newInstance());
        }

        foreach($reflectionClass->getProperties() as $property) {
            $propertyAnnotationsDump = new PropertyAnnotationsDump($property->getName());
            foreach($property->getAttributes() as $attribute) {
                $propertyAnnotationsDump->addAnnotation($attribute->getName(), $attribute->newInstance());
            }

            $classAnnotationsDump->addProperty($propertyAnnotationsDump);
        }

        return $classAnnotationsDump;
    }

    public static function hasAnnotation(string $annotation, string $className): bool {
        if(class_exists($className) === false) {
            throw new \Exception('Not a class');
        }
    }
}

?>
