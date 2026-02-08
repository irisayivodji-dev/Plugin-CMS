<?php


namespace App\Lib\Annotations\ORM;

use App\Lib\Annotations\AbstractAnnotation;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class References extends AbstractAnnotation{
    public function __construct(
        public string $class,
        public string $property
    ){}
}

?>
