<?php
 namespace Symfony\Component\PropertyAccess\Tests\Fixtures; class TypeHinted { private $date; private $countable; public function setDate(\DateTime $date) { $this->date = $date; } public function getDate() { return $this->date; } public function getCountable() { return $this->countable; } public function setCountable(\Countable $countable) { $this->countable = $countable; } } 