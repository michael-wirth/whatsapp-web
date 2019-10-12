<?php
 namespace Doctrine\Common\Persistence; abstract class AbstractManagerRegistry implements ManagerRegistry { private $name; private $connections; private $managers; private $defaultConnection; private $defaultManager; private $proxyInterfaceName; public function __construct($name, array $connections, array $managers, $defaultConnection, $defaultManager, $proxyInterfaceName) { $this->name = $name; $this->connections = $connections; $this->managers = $managers; $this->defaultConnection = $defaultConnection; $this->defaultManager = $defaultManager; $this->proxyInterfaceName = $proxyInterfaceName; } abstract protected function getService($name); abstract protected function resetService($name); public function getName() { return $this->name; } public function getConnection($name = null) { if (null === $name) { $name = $this->defaultConnection; } if (!isset($this->connections[$name])) { throw new \InvalidArgumentException(sprintf('Doctrine %s Connection named "%s" does not exist.', $this->name, $name)); } return $this->getService($this->connections[$name]); } public function getConnectionNames() { return $this->connections; } public function getConnections() { $connections = []; foreach ($this->connections as $name => $id) { $connections[$name] = $this->getService($id); } return $connections; } public function getDefaultConnectionName() { return $this->defaultConnection; } public function getDefaultManagerName() { return $this->defaultManager; } public function getManager($name = null) { if (null === $name) { $name = $this->defaultManager; } if (!isset($this->managers[$name])) { throw new \InvalidArgumentException(sprintf('Doctrine %s Manager named "%s" does not exist.', $this->name, $name)); } return $this->getService($this->managers[$name]); } public function getManagerForClass($class) { if (strpos($class, ':') !== false) { list($namespaceAlias, $simpleClassName) = explode(':', $class, 2); $class = $this->getAliasNamespace($namespaceAlias) . '\\' . $simpleClassName; } $proxyClass = new \ReflectionClass($class); if ($proxyClass->implementsInterface($this->proxyInterfaceName)) { if (! $parentClass = $proxyClass->getParentClass()) { return null; } $class = $parentClass->getName(); } foreach ($this->managers as $id) { $manager = $this->getService($id); if (!$manager->getMetadataFactory()->isTransient($class)) { return $manager; } } } public function getManagerNames() { return $this->managers; } public function getManagers() { $dms = []; foreach ($this->managers as $name => $id) { $dms[$name] = $this->getService($id); } return $dms; } public function getRepository($persistentObjectName, $persistentManagerName = null) { return $this->getManager($persistentManagerName)->getRepository($persistentObjectName); } public function resetManager($name = null) { if (null === $name) { $name = $this->defaultManager; } if (!isset($this->managers[$name])) { throw new \InvalidArgumentException(sprintf('Doctrine %s Manager named "%s" does not exist.', $this->name, $name)); } $this->resetService($this->managers[$name]); return $this->getManager($name); } } 