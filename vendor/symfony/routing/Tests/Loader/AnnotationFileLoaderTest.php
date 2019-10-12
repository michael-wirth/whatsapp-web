<?php
 namespace Symfony\Component\Routing\Tests\Loader; use Symfony\Component\Routing\Loader\AnnotationFileLoader; use Symfony\Component\Config\FileLocator; use Symfony\Component\Routing\Annotation\Route; class AnnotationFileLoaderTest extends AbstractAnnotationLoaderTest { protected $loader; protected $reader; protected function setUp() { parent::setUp(); $this->reader = $this->getReader(); $this->loader = new AnnotationFileLoader(new FileLocator(), $this->getClassLoader($this->reader)); } public function testLoad() { $this->reader->expects($this->once())->method('getClassAnnotation'); $this->loader->load(__DIR__.'/../Fixtures/AnnotatedClasses/FooClass.php'); } public function testLoadTraitWithClassConstant() { $this->reader->expects($this->never())->method('getClassAnnotation'); $this->loader->load(__DIR__.'/../Fixtures/AnnotatedClasses/FooTrait.php'); } public function testLoadFileWithoutStartTag() { $this->loader->load(__DIR__.'/../Fixtures/OtherAnnotatedClasses/NoStartTagClass.php'); } public function testLoadVariadic() { $route = new Route(array('path' => '/path/to/{id}')); $this->reader->expects($this->once())->method('getClassAnnotation'); $this->reader->expects($this->once())->method('getMethodAnnotations') ->will($this->returnValue(array($route))); $this->loader->load(__DIR__.'/../Fixtures/OtherAnnotatedClasses/VariadicClass.php'); } public function testSupports() { $fixture = __DIR__.'/../Fixtures/annotated.php'; $this->assertTrue($this->loader->supports($fixture), '->supports() returns true if the resource is loadable'); $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable'); $this->assertTrue($this->loader->supports($fixture, 'annotation'), '->supports() checks the resource type if specified'); $this->assertFalse($this->loader->supports($fixture, 'foo'), '->supports() checks the resource type if specified'); } } 