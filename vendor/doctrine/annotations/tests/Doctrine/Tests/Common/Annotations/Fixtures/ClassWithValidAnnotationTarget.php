<?php
 namespace Doctrine\Tests\Common\Annotations\Fixtures; use Doctrine\Tests\Common\Annotations\Fixtures\AnnotationTargetClass; use Doctrine\Tests\Common\Annotations\Fixtures\AnnotationTargetAll; use Doctrine\Tests\Common\Annotations\Fixtures\AnnotationTargetPropertyMethod; class ClassWithValidAnnotationTarget { public $foo; public $name; public function someFunction() { } public $nested; } 