<?php


namespace App\Lib\Annotations\ORM;

use App\Lib\Annotations\AbstractAnnotation;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column extends AbstractAnnotation{
    public function __construct(
        public string $type,
        public bool $nullable = false,
        public int|null $size = null,
        public string|null $name = null
    ){}
}

?>
