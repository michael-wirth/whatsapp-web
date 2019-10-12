<?php
 namespace GeckoPackages\Silex\Services\Config; use GeckoPackages\Silex\Services\Config\Loader\JsonLoader; use GeckoPackages\Silex\Services\Config\Loader\LoaderInterface; use GeckoPackages\Silex\Services\Config\Loader\PHPLoader; use GeckoPackages\Silex\Services\Config\Loader\YamlLoader; use Silex\Application; use Symfony\Component\Filesystem\Exception\FileNotFoundException; use Symfony\Component\Filesystem\Exception\IOException; class ConfigLoader implements \ArrayAccess { private $app; private $config = array(); private $configDirectory; private $loader; private $format; private $cache; private $environment; public function __construct(Application $app, $dir = null, $format = '%key%.json', $cache = null, $environment = null) { $this->app = $app; if (null !== $dir) { $this->setDir($dir); } $this->setFormat($format); $this->environment = null === $environment ? '' : $environment; $this->cache = $cache; } public function get($key) { if (isset($this->config[$key])) { return $this->config[$key]['config']; } $conf = null; $file = $this->getFileNameForKey($key); if (null !== $this->cache) { $cacheKey = $this->getCacheKeyForFile($file); $conf = $this->app[$this->cache]->get($cacheKey); if (false !== $conf) { $this->config[$key] = array( 'config' => $conf, 'cacheKey' => $cacheKey, ); return $conf; } } $conf = $this->loader->getConfig($file); $this->config[$key] = array('config' => $conf); if (null !== $this->cache) { $this->config[$key]['cacheKey'] = $cacheKey; $this->app[$this->cache]->set($cacheKey, $conf); } return $conf; } public function getDir() { return $this->configDirectory; } public function setCache($cache) { $this->cache = $cache; } public function setDir($dir) { if (!is_dir($dir)) { throw new FileNotFoundException(sprintf('Config "%s" is not a directory.', is_string($dir) ? $dir : (is_object($dir) ? get_class($dir) : gettype($dir)))); } $newDir = realpath($dir).'/'; if (null === $this->configDirectory) { $this->configDirectory = $newDir; return; } if ($newDir === $this->configDirectory) { return; } $this->configDirectory = $newDir; $this->flushAll(); } public function setEnvironment($environment) { $this->environment = null === $environment ? '' : $environment; $this->flushAll(); } public function setFormat($format) { if ($this->app['debug']) { if (false === is_string($format)) { throw new \InvalidArgumentException(sprintf('Format must be a string, got "%s".', is_object($format) ? get_class($format) : gettype($format))); } if (false === strpos($format, '%key%')) { throw new \InvalidArgumentException(sprintf('Format must contain "%%key%%", got "%s".', $format)); } if (false === strrpos($format, '.')) { throw new \InvalidArgumentException(sprintf('Format missing extension, got "%s".', $format)); } } $this->format = $format; if (strlen($format) > 5 && '.dist' === substr($format, -5)) { $format = substr($format, strrpos($format, '.', -6) + 1); } else { $format = substr($format, strrpos($format, '.') + 1); } switch ($format) { case 'json': case 'json.dist': $this->loader = new JsonLoader(); break; case 'yml': case 'yml.dist': case 'yaml': case 'yaml.dist': if (false === class_exists('Symfony\\Component\\Yaml\\Yaml')) { throw new \RuntimeException('Missing Symfony Yaml component.'); } $this->loader = new YamlLoader(); break; case 'php': case 'php.dist': $this->loader = new PHPLoader(); break; default : throw new \InvalidArgumentException(sprintf('Unsupported file format "%s".', $format)); } $this->flushAll(); } public function flushAll() { if (null !== $this->cache) { foreach ($this->config as $config) { if (array_key_exists('cacheKey', $config)) { $this->app[$this->cache]->delete($config['cacheKey']); } } } $this->config = array(); } public function flushConfig($key) { if (null !== $this->cache) { if (isset($this->config[$key]) && array_key_exists('cacheKey', $this->config[$key])) { $cacheKey = $this->config[$key]['cacheKey']; } else { $cacheKey = $this->getCacheKeyForFile($this->getFileNameForKey($key)); } $this->app[$this->cache]->delete($cacheKey); } unset($this->config[$key]); } public function __isset($name) { try { return is_array($this->get($name)); } catch (FileNotFoundException $e) { } return false; } public function __get($name) { return $this->get($name); } public function offsetExists($offset) { return $this->__isset($offset); } public function offsetGet($offset) { return $this->get($offset); } public function offsetSet($offset, $value) { throw new \BadMethodCallException('"offsetSet" is not supported.'); } public function offsetUnset($offset) { throw new \BadMethodCallException('"offsetUnset" is not supported.'); } private function getCacheKeyForFile($file) { return 'conf:'.abs(crc32($file)); } private function getFileNameForKey($key) { return $this->getDir().strtr($this->format, array('%key%' => $key, '%env%' => $this->environment)); } } 