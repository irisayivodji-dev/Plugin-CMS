<?php


namespace App\Lib\Annotations\ORM;

use App\Lib\Annotations\AbstractAnnotation;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ORM extends AbstractAnnotation{}

?>
