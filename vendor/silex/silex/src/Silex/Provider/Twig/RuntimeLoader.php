<?php
 namespace Silex\Provider\Twig; use Pimple\Container; class RuntimeLoader implements \Twig_RuntimeLoaderInterface { private $container; private $mapping; public function __construct(Container $container, array $mapping) { $this->container = $container; $this->mapping = $mapping; } public function load($class) { if (isset($this->mapping[$class])) { return $this->container[$this->mapping[$class]]; } } } 