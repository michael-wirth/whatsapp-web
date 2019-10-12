<?php
 namespace Silex\Tests\Application; use PHPUnit\Framework\TestCase; use Silex\Provider\TranslationServiceProvider; class TranslationTraitTest extends TestCase { public function testTrans() { $app = $this->createApplication(); $app['translator'] = $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')->disableOriginalConstructor()->getMock(); $translator->expects($this->once())->method('trans'); $app->trans('foo'); } public function testTransChoice() { $app = $this->createApplication(); $app['translator'] = $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')->disableOriginalConstructor()->getMock(); $translator->expects($this->once())->method('transChoice'); $app->transChoice('foo', 2); } public function createApplication() { $app = new TranslationApplication(); $app->register(new TranslationServiceProvider()); return $app; } } 