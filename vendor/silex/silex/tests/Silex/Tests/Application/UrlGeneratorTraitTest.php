<?php
 namespace Silex\Tests\Application; use PHPUnit\Framework\TestCase; use Symfony\Component\Routing\Generator\UrlGeneratorInterface; class UrlGeneratorTraitTest extends TestCase { public function testUrl() { $app = new UrlGeneratorApplication(); $app['url_generator'] = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGeneratorInterface')->disableOriginalConstructor()->getMock(); $app['url_generator']->expects($this->once())->method('generate')->with('foo', array(), UrlGeneratorInterface::ABSOLUTE_URL); $app->url('foo'); } public function testPath() { $app = new UrlGeneratorApplication(); $app['url_generator'] = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGeneratorInterface')->disableOriginalConstructor()->getMock(); $app['url_generator']->expects($this->once())->method('generate')->with('foo', array(), UrlGeneratorInterface::ABSOLUTE_PATH); $app->path('foo'); } } 