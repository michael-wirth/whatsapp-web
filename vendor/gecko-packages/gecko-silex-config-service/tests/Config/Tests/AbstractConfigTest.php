<?php
 namespace GeckoPackages\Silex\Services\Config\Tests; use GeckoPackages\Silex\Services\Config\ConfigServiceProvider; use Silex\Application; abstract class AbstractConfigTest extends \PHPUnit_Framework_TestCase { protected function getConfigDir() { return __DIR__.'/../../assets/config'; } protected function setupConfigService(Application $app, $format = '%key%.json', $cache = null, $env = null) { $app->register( new ConfigServiceProvider(), array( 'config.dir' => $this->getConfigDir(), 'config.format' => $format, 'config.cache' => $cache, 'config.env' => $env, ) ); } } 