<?php
 namespace Doctrine\Tests\Common\Annotations\Ticket; use Doctrine\Tests\Common\Annotations\Fixtures\Controller; use Doctrine\Common\Annotations\AnnotationReader; class DCOM55Test extends \PHPUnit_Framework_TestCase { public function testIssue() { $class = new \ReflectionClass(__NAMESPACE__ . '\\Dummy'); $reader = new AnnotationReader(); $reader->getClassAnnotations($class); } public function testAnnotation() { $class = new \ReflectionClass(__NAMESPACE__ . '\\DCOM55Consumer'); $reader = new AnnotationReader(); $annots = $reader->getClassAnnotations($class); self::assertCount(1, $annots); self::assertInstanceOf(__NAMESPACE__.'\\DCOM55Annotation', $annots[0]); } public function testParseAnnotationDocblocks() { $class = new \ReflectionClass(__NAMESPACE__ . '\\DCOM55Annotation'); $reader = new AnnotationReader(); $annots = $reader->getClassAnnotations($class); self::assertEmpty($annots); } } class Dummy { } class DCOM55Annotation { } class DCOM55Consumer { }