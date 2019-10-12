<?php
 namespace Symfony\Component\HttpKernel\Tests\CacheClearer; use PHPUnit\Framework\TestCase; use Symfony\Component\HttpKernel\CacheClearer\ChainCacheClearer; class ChainCacheClearerTest extends TestCase { protected static $cacheDir; public static function setUpBeforeClass() { self::$cacheDir = tempnam(sys_get_temp_dir(), 'sf2_cache_clearer_dir'); } public static function tearDownAfterClass() { @unlink(self::$cacheDir); } public function testInjectClearersInConstructor() { $clearer = $this->getMockClearer(); $clearer ->expects($this->once()) ->method('clear'); $chainClearer = new ChainCacheClearer(array($clearer)); $chainClearer->clear(self::$cacheDir); } public function testInjectClearerUsingAdd() { $clearer = $this->getMockClearer(); $clearer ->expects($this->once()) ->method('clear'); $chainClearer = new ChainCacheClearer(); $chainClearer->add($clearer); $chainClearer->clear(self::$cacheDir); } protected function getMockClearer() { return $this->getMockBuilder('Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface')->getMock(); } } 