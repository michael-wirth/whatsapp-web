<?php
 namespace Symfony\Component\HttpKernel; use Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator; use Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper; use Symfony\Component\DependencyInjection\ContainerInterface; use Symfony\Component\DependencyInjection\ContainerBuilder; use Symfony\Component\DependencyInjection\Dumper\PhpDumper; use Symfony\Component\DependencyInjection\Loader\XmlFileLoader; use Symfony\Component\DependencyInjection\Loader\YamlFileLoader; use Symfony\Component\DependencyInjection\Loader\IniFileLoader; use Symfony\Component\DependencyInjection\Loader\PhpFileLoader; use Symfony\Component\DependencyInjection\Loader\DirectoryLoader; use Symfony\Component\DependencyInjection\Loader\ClosureLoader; use Symfony\Component\HttpFoundation\Request; use Symfony\Component\HttpFoundation\Response; use Symfony\Component\HttpKernel\Bundle\BundleInterface; use Symfony\Component\HttpKernel\Config\EnvParametersResource; use Symfony\Component\HttpKernel\Config\FileLocator; use Symfony\Component\HttpKernel\DependencyInjection\MergeExtensionConfigurationPass; use Symfony\Component\HttpKernel\DependencyInjection\AddAnnotatedClassesToCachePass; use Symfony\Component\Config\Loader\GlobFileLoader; use Symfony\Component\Config\Loader\LoaderResolver; use Symfony\Component\Config\Loader\DelegatingLoader; use Symfony\Component\Config\ConfigCache; use Symfony\Component\ClassLoader\ClassCollectionLoader; abstract class Kernel implements KernelInterface, TerminableInterface { protected $bundles = array(); protected $bundleMap; protected $container; protected $rootDir; protected $environment; protected $debug; protected $booted = false; protected $name; protected $startTime; protected $loadClassCache; private $projectDir; const VERSION = '3.3.6'; const VERSION_ID = 30306; const MAJOR_VERSION = 3; const MINOR_VERSION = 3; const RELEASE_VERSION = 6; const EXTRA_VERSION = ''; const END_OF_MAINTENANCE = '01/2018'; const END_OF_LIFE = '07/2018'; public function __construct($environment, $debug) { $this->environment = $environment; $this->debug = (bool) $debug; $this->rootDir = $this->getRootDir(); $this->name = $this->getName(); if ($this->debug) { $this->startTime = microtime(true); } } public function __clone() { if ($this->debug) { $this->startTime = microtime(true); } $this->booted = false; $this->container = null; } public function boot() { if (true === $this->booted) { return; } if ($this->loadClassCache) { $this->doLoadClassCache($this->loadClassCache[0], $this->loadClassCache[1]); } $this->initializeBundles(); $this->initializeContainer(); foreach ($this->getBundles() as $bundle) { $bundle->setContainer($this->container); $bundle->boot(); } $this->booted = true; } public function terminate(Request $request, Response $response) { if (false === $this->booted) { return; } if ($this->getHttpKernel() instanceof TerminableInterface) { $this->getHttpKernel()->terminate($request, $response); } } public function shutdown() { if (false === $this->booted) { return; } $this->booted = false; foreach ($this->getBundles() as $bundle) { $bundle->shutdown(); $bundle->setContainer(null); } $this->container = null; } public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true) { if (false === $this->booted) { $this->boot(); } return $this->getHttpKernel()->handle($request, $type, $catch); } protected function getHttpKernel() { return $this->container->get('http_kernel'); } public function getBundles() { return $this->bundles; } public function getBundle($name, $first = true) { if (!isset($this->bundleMap[$name])) { throw new \InvalidArgumentException(sprintf('Bundle "%s" does not exist or it is not enabled. Maybe you forgot to add it in the registerBundles() method of your %s.php file?', $name, get_class($this))); } if (true === $first) { return $this->bundleMap[$name][0]; } return $this->bundleMap[$name]; } public function locateResource($name, $dir = null, $first = true) { if ('@' !== $name[0]) { throw new \InvalidArgumentException(sprintf('A resource name must start with @ ("%s" given).', $name)); } if (false !== strpos($name, '..')) { throw new \RuntimeException(sprintf('File name "%s" contains invalid characters (..).', $name)); } $bundleName = substr($name, 1); $path = ''; if (false !== strpos($bundleName, '/')) { list($bundleName, $path) = explode('/', $bundleName, 2); } $isResource = 0 === strpos($path, 'Resources') && null !== $dir; $overridePath = substr($path, 9); $resourceBundle = null; $bundles = $this->getBundle($bundleName, false); $files = array(); foreach ($bundles as $bundle) { if ($isResource && file_exists($file = $dir.'/'.$bundle->getName().$overridePath)) { if (null !== $resourceBundle) { throw new \RuntimeException(sprintf('"%s" resource is hidden by a resource from the "%s" derived bundle. Create a "%s" file to override the bundle resource.', $file, $resourceBundle, $dir.'/'.$bundles[0]->getName().$overridePath )); } if ($first) { return $file; } $files[] = $file; } if (file_exists($file = $bundle->getPath().'/'.$path)) { if ($first && !$isResource) { return $file; } $files[] = $file; $resourceBundle = $bundle->getName(); } } if (count($files) > 0) { return $first && $isResource ? $files[0] : $files; } throw new \InvalidArgumentException(sprintf('Unable to find file "%s".', $name)); } public function getName() { if (null === $this->name) { $this->name = preg_replace('/[^a-zA-Z0-9_]+/', '', basename($this->rootDir)); if (ctype_digit($this->name[0])) { $this->name = '_'.$this->name; } } return $this->name; } public function getEnvironment() { return $this->environment; } public function isDebug() { return $this->debug; } public function getRootDir() { if (null === $this->rootDir) { $r = new \ReflectionObject($this); $this->rootDir = dirname($r->getFileName()); } return $this->rootDir; } public function getProjectDir() { if (null === $this->projectDir) { $r = new \ReflectionObject($this); $dir = $rootDir = dirname($r->getFileName()); while (!file_exists($dir.'/composer.json')) { if ($dir === dirname($dir)) { return $this->projectDir = $rootDir; } $dir = dirname($dir); } $this->projectDir = $dir; } return $this->projectDir; } public function getContainer() { return $this->container; } public function loadClassCache($name = 'classes', $extension = '.php') { if (\PHP_VERSION_ID >= 70000) { @trigger_error(__METHOD__.'() is deprecated since version 3.3, to be removed in 4.0.', E_USER_DEPRECATED); } $this->loadClassCache = array($name, $extension); } public function setClassCache(array $classes) { if (\PHP_VERSION_ID >= 70000) { @trigger_error(__METHOD__.'() is deprecated since version 3.3, to be removed in 4.0.', E_USER_DEPRECATED); } file_put_contents($this->getCacheDir().'/classes.map', sprintf('<?php return %s;', var_export($classes, true))); } public function setAnnotatedClassCache(array $annotatedClasses) { file_put_contents($this->getCacheDir().'/annotations.map', sprintf('<?php return %s;', var_export($annotatedClasses, true))); } public function getStartTime() { return $this->debug ? $this->startTime : -INF; } public function getCacheDir() { return $this->rootDir.'/cache/'.$this->environment; } public function getLogDir() { return $this->rootDir.'/logs'; } public function getCharset() { return 'UTF-8'; } protected function doLoadClassCache($name, $extension) { if (\PHP_VERSION_ID >= 70000) { @trigger_error(__METHOD__.'() is deprecated since version 3.3, to be removed in 4.0.', E_USER_DEPRECATED); } if (!$this->booted && is_file($this->getCacheDir().'/classes.map')) { ClassCollectionLoader::load(include($this->getCacheDir().'/classes.map'), $this->getCacheDir(), $name, $this->debug, false, $extension); } } protected function initializeBundles() { $this->bundles = array(); $topMostBundles = array(); $directChildren = array(); foreach ($this->registerBundles() as $bundle) { $name = $bundle->getName(); if (isset($this->bundles[$name])) { throw new \LogicException(sprintf('Trying to register two bundles with the same name "%s"', $name)); } $this->bundles[$name] = $bundle; if ($parentName = $bundle->getParent()) { if (isset($directChildren[$parentName])) { throw new \LogicException(sprintf('Bundle "%s" is directly extended by two bundles "%s" and "%s".', $parentName, $name, $directChildren[$parentName])); } if ($parentName == $name) { throw new \LogicException(sprintf('Bundle "%s" can not extend itself.', $name)); } $directChildren[$parentName] = $name; } else { $topMostBundles[$name] = $bundle; } } if (!empty($directChildren) && count($diff = array_diff_key($directChildren, $this->bundles))) { $diff = array_keys($diff); throw new \LogicException(sprintf('Bundle "%s" extends bundle "%s", which is not registered.', $directChildren[$diff[0]], $diff[0])); } $this->bundleMap = array(); foreach ($topMostBundles as $name => $bundle) { $bundleMap = array($bundle); $hierarchy = array($name); while (isset($directChildren[$name])) { $name = $directChildren[$name]; array_unshift($bundleMap, $this->bundles[$name]); $hierarchy[] = $name; } foreach ($hierarchy as $hierarchyBundle) { $this->bundleMap[$hierarchyBundle] = $bundleMap; array_pop($bundleMap); } } } protected function build(ContainerBuilder $container) { } protected function getContainerClass() { return $this->name.ucfirst($this->environment).($this->debug ? 'Debug' : '').'ProjectContainer'; } protected function getContainerBaseClass() { return 'Container'; } protected function initializeContainer() { $class = $this->getContainerClass(); $cache = new ConfigCache($this->getCacheDir().'/'.$class.'.php', $this->debug); $fresh = true; if (!$cache->isFresh()) { if ($this->debug) { $collectedLogs = array(); $previousHandler = set_error_handler(function ($type, $message, $file, $line) use (&$collectedLogs, &$previousHandler) { if (E_USER_DEPRECATED !== $type && E_DEPRECATED !== $type) { return $previousHandler ? $previousHandler($type, $message, $file, $line) : false; } if (isset($collectedLogs[$message])) { ++$collectedLogs[$message]['count']; return; } $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3); for ($i = 0; isset($backtrace[$i]); ++$i) { if (isset($backtrace[$i]['file'], $backtrace[$i]['line']) && $backtrace[$i]['line'] === $line && $backtrace[$i]['file'] === $file) { $backtrace = array_slice($backtrace, 1 + $i); break; } } $collectedLogs[$message] = array( 'type' => $type, 'message' => $message, 'file' => $file, 'line' => $line, 'trace' => $backtrace, 'count' => 1, ); }); } try { $container = null; $container = $this->buildContainer(); $container->compile(); } finally { if ($this->debug) { restore_error_handler(); file_put_contents($this->getCacheDir().'/'.$class.'Deprecations.log', serialize(array_values($collectedLogs))); file_put_contents($this->getCacheDir().'/'.$class.'Compiler.log', null !== $container ? implode("\n", $container->getCompiler()->getLog()) : ''); } } $this->dumpContainer($cache, $container, $class, $this->getContainerBaseClass()); $fresh = false; } require_once $cache->getPath(); $this->container = new $class(); $this->container->set('kernel', $this); if (!$fresh && $this->container->has('cache_warmer')) { $this->container->get('cache_warmer')->warmUp($this->container->getParameter('kernel.cache_dir')); } } protected function getKernelParameters() { $bundles = array(); $bundlesMetadata = array(); foreach ($this->bundles as $name => $bundle) { $bundles[$name] = get_class($bundle); $bundlesMetadata[$name] = array( 'parent' => $bundle->getParent(), 'path' => $bundle->getPath(), 'namespace' => $bundle->getNamespace(), ); } return array_merge( array( 'kernel.root_dir' => realpath($this->rootDir) ?: $this->rootDir, 'kernel.project_dir' => realpath($this->getProjectDir()) ?: $this->getProjectDir(), 'kernel.environment' => $this->environment, 'kernel.debug' => $this->debug, 'kernel.name' => $this->name, 'kernel.cache_dir' => realpath($this->getCacheDir()) ?: $this->getCacheDir(), 'kernel.logs_dir' => realpath($this->getLogDir()) ?: $this->getLogDir(), 'kernel.bundles' => $bundles, 'kernel.bundles_metadata' => $bundlesMetadata, 'kernel.charset' => $this->getCharset(), 'kernel.container_class' => $this->getContainerClass(), ), $this->getEnvParameters(false) ); } protected function getEnvParameters() { if (0 === func_num_args() || func_get_arg(0)) { @trigger_error(sprintf('The %s() method is deprecated as of 3.3 and will be removed in 4.0. Use the %%env()%% syntax to get the value of any environment variable from configuration files instead.', __METHOD__), E_USER_DEPRECATED); } $parameters = array(); foreach ($_SERVER as $key => $value) { if (0 === strpos($key, 'SYMFONY__')) { @trigger_error(sprintf('The support of special environment variables that start with SYMFONY__ (such as "%s") is deprecated as of 3.3 and will be removed in 4.0. Use the %%env()%% syntax instead to get the value of environment variables in configuration files.', $key), E_USER_DEPRECATED); $parameters[strtolower(str_replace('__', '.', substr($key, 9)))] = $value; } } return $parameters; } protected function buildContainer() { foreach (array('cache' => $this->getCacheDir(), 'logs' => $this->getLogDir()) as $name => $dir) { if (!is_dir($dir)) { if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) { throw new \RuntimeException(sprintf("Unable to create the %s directory (%s)\n", $name, $dir)); } } elseif (!is_writable($dir)) { throw new \RuntimeException(sprintf("Unable to write in the %s directory (%s)\n", $name, $dir)); } } $container = $this->getContainerBuilder(); $container->addObjectResource($this); $this->prepareContainer($container); if (null !== $cont = $this->registerContainerConfiguration($this->getContainerLoader($container))) { $container->merge($cont); } $container->addCompilerPass(new AddAnnotatedClassesToCachePass($this)); $container->addResource(new EnvParametersResource('SYMFONY__')); return $container; } protected function prepareContainer(ContainerBuilder $container) { $extensions = array(); foreach ($this->bundles as $bundle) { if ($extension = $bundle->getContainerExtension()) { $container->registerExtension($extension); $extensions[] = $extension->getAlias(); } if ($this->debug) { $container->addObjectResource($bundle); } } foreach ($this->bundles as $bundle) { $bundle->build($container); } $this->build($container); $container->getCompilerPassConfig()->setMergePass(new MergeExtensionConfigurationPass($extensions)); } protected function getContainerBuilder() { $container = new ContainerBuilder(); $container->getParameterBag()->add($this->getKernelParameters()); if (class_exists('ProxyManager\Configuration') && class_exists('Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator')) { $container->setProxyInstantiator(new RuntimeInstantiator()); } return $container; } protected function dumpContainer(ConfigCache $cache, ContainerBuilder $container, $class, $baseClass) { $dumper = new PhpDumper($container); if (class_exists('ProxyManager\Configuration') && class_exists('Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper')) { $dumper->setProxyDumper(new ProxyDumper(md5($cache->getPath()))); } $content = $dumper->dump(array('class' => $class, 'base_class' => $baseClass, 'file' => $cache->getPath(), 'debug' => $this->debug)); $cache->write($content, $container->getResources()); } protected function getContainerLoader(ContainerInterface $container) { $locator = new FileLocator($this); $resolver = new LoaderResolver(array( new XmlFileLoader($container, $locator), new YamlFileLoader($container, $locator), new IniFileLoader($container, $locator), new PhpFileLoader($container, $locator), new GlobFileLoader($locator), new DirectoryLoader($container, $locator), new ClosureLoader($container), )); return new DelegatingLoader($resolver); } public static function stripComments($source) { if (!function_exists('token_get_all')) { return $source; } $rawChunk = ''; $output = ''; $tokens = token_get_all($source); $ignoreSpace = false; for ($i = 0; isset($tokens[$i]); ++$i) { $token = $tokens[$i]; if (!isset($token[1]) || 'b"' === $token) { $rawChunk .= $token; } elseif (T_START_HEREDOC === $token[0]) { $output .= $rawChunk.$token[1]; do { $token = $tokens[++$i]; $output .= isset($token[1]) && 'b"' !== $token ? $token[1] : $token; } while ($token[0] !== T_END_HEREDOC); $rawChunk = ''; } elseif (T_WHITESPACE === $token[0]) { if ($ignoreSpace) { $ignoreSpace = false; continue; } $rawChunk .= preg_replace(array('/\n{2,}/S'), "\n", $token[1]); } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) { $ignoreSpace = true; } else { $rawChunk .= $token[1]; if (T_OPEN_TAG === $token[0]) { $ignoreSpace = true; } } } $output .= $rawChunk; if (\PHP_VERSION_ID >= 70000) { unset($tokens, $rawChunk); gc_mem_caches(); } return $output; } public function serialize() { return serialize(array($this->environment, $this->debug)); } public function unserialize($data) { if (\PHP_VERSION_ID >= 70000) { list($environment, $debug) = unserialize($data, array('allowed_classes' => false)); } else { list($environment, $debug) = unserialize($data); } $this->__construct($environment, $debug); } } 