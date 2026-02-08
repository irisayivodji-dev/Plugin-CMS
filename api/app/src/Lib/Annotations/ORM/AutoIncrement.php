<?php


namespace App\Lib\Annotations\ORM;

use App\Lib\Annotations\AbstractAnnotation;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class AutoIncrement extends AbstractAnnotation {}

?>
