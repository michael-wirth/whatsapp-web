<?php
 namespace Doctrine\Tests\Common\Proxy; class MagicIssetClass { public $id = 'id'; public $publicField = 'publicField'; public function __isset($name) { if ('test' === $name) { return true; } if ('publicField' === $name || 'id' === $name) { throw new \BadMethodCallException('Should never be called for "publicField" or "id"'); } return false; } } 