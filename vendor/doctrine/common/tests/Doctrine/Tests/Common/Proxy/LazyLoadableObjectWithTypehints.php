<?php
 namespace Doctrine\Tests\Common\Proxy; use Doctrine; use stdClass as A; class LazyLoadableObjectWithTypehints { private $identifierFieldNoReturnTypehint; private $identifierFieldReturnTypehintScalar; private $identifierFieldReturnClassFullyQualified; private $identifierFieldReturnClassPartialUse; private $identifierFieldReturnClassFullUse; private $identifierFieldReturnClassOneWord; private $identifierFieldReturnClassOneLetter; public function getIdentifierFieldNoReturnTypehint() { return $this->identifierFieldNoReturnTypehint; } public function getIdentifierFieldReturnTypehintScalar(): string { return $this->identifierFieldReturnTypehintScalar; } public function getIdentifierFieldReturnClassFullyQualified(): \Doctrine\Tests\Common\Proxy\LazyLoadableObjectWithTypehints { return $this->identifierFieldReturnClassFullyQualified; } public function getIdentifierFieldReturnClassPartialUse(): Doctrine\Tests\Common\Proxy\LazyLoadableObjectWithTypehints { return $this->identifierFieldReturnClassPartialUse; } public function getIdentifierFieldReturnClassFullUse(): LazyLoadableObjectWithTypehints { return $this->identifierFieldReturnClassFullUse; } public function getIdentifierFieldReturnClassOneWord(): \stdClass { return $this->identifierFieldReturnClassOneWord; } public function getIdentifierFieldReturnClassOneLetter(): A { return $this->identifierFieldReturnClassOneLetter; } } 