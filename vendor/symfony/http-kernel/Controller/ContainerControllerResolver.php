<?php
 namespace Symfony\Component\HttpKernel\Controller; use Psr\Container\ContainerInterface; use Psr\Log\LoggerInterface; class ContainerControllerResolver extends ControllerResolver { protected $container; public function __construct(ContainerInterface $container, LoggerInterface $logger = null) { $this->container = $container; parent::__construct($logger); } protected function createController($controller) { if (false !== strpos($controller, '::')) { return parent::createController($controller); } if (1 == substr_count($controller, ':')) { list($service, $method) = explode(':', $controller, 2); return array($this->container->get($service), $method); } if ($this->container->has($controller) && method_exists($service = $this->container->get($controller), '__invoke')) { return $service; } throw new \LogicException(sprintf('Unable to parse the controller name "%s".', $controller)); } protected function instantiateController($class) { if ($this->container->has($class)) { return $this->container->get($class); } return parent::instantiateController($class); } } 