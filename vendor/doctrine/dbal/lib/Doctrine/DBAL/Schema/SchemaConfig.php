<?php
 namespace Doctrine\DBAL\Schema; class SchemaConfig { protected $hasExplicitForeignKeyIndexes = false; protected $maxIdentifierLength = 63; protected $name; protected $defaultTableOptions = array(); public function hasExplicitForeignKeyIndexes() { return $this->hasExplicitForeignKeyIndexes; } public function setExplicitForeignKeyIndexes($flag) { $this->hasExplicitForeignKeyIndexes = (bool) $flag; } public function setMaxIdentifierLength($length) { $this->maxIdentifierLength = (int) $length; } public function getMaxIdentifierLength() { return $this->maxIdentifierLength; } public function getName() { return $this->name; } public function setName($name) { $this->name = $name; } public function getDefaultTableOptions() { return $this->defaultTableOptions; } public function setDefaultTableOptions(array $defaultTableOptions) { $this->defaultTableOptions = $defaultTableOptions; } } 