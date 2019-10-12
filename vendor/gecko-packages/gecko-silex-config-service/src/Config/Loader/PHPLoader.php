<?php
 namespace GeckoPackages\Silex\Services\Config\Loader; use Symfony\Component\Filesystem\Exception\FileNotFoundException; final class PHPLoader implements LoaderInterface { public function getConfig($file) { if (false === is_file($file)) { throw new FileNotFoundException(sprintf('Config file not found "%s".', $file)); } $config = require $file; if (false === is_array($config)) { throw new \UnexpectedValueException(sprintf('Expected array as configuration, got: "%s", in "%s".', gettype($config), $file)); } return $config; } } 