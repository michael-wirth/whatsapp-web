<?php
 namespace Doctrine\Tests\Common\Proxy; use ReflectionClass; use Doctrine\Common\Persistence\Mapping\ClassMetadata; class LazyLoadableObjectClassMetadata implements ClassMetadata { protected $reflectionClass; protected $identifier = [ 'publicIdentifierField' => true, 'protectedIdentifierField' => true, ]; protected $fields = [ 'publicIdentifierField' => true, 'protectedIdentifierField' => true, 'publicPersistentField' => true, 'protectedPersistentField' => true, ]; protected $associations = [ 'publicAssociation' => true, 'protectedAssociation' => true, ]; public function getName() { return $this->getReflectionClass()->getName(); } public function getIdentifier() { return array_keys($this->identifier); } public function getReflectionClass() { if (null === $this->reflectionClass) { $this->reflectionClass = new \ReflectionClass(__NAMESPACE__ . '\LazyLoadableObject'); } return $this->reflectionClass; } public function isIdentifier($fieldName) { return isset($this->identifier[$fieldName]); } public function hasField($fieldName) { return isset($this->fields[$fieldName]); } public function hasAssociation($fieldName) { return isset($this->associations[$fieldName]); } public function isSingleValuedAssociation($fieldName) { throw new \BadMethodCallException('not implemented'); } public function isCollectionValuedAssociation($fieldName) { throw new \BadMethodCallException('not implemented'); } public function getFieldNames() { return array_keys($this->fields); } public function getIdentifierFieldNames() { return $this->getIdentifier(); } public function getAssociationNames() { return array_keys($this->associations); } public function getTypeOfField($fieldName) { return 'string'; } public function getAssociationTargetClass($assocName) { throw new \BadMethodCallException('not implemented'); } public function isAssociationInverseSide($assocName) { throw new \BadMethodCallException('not implemented'); } public function getAssociationMappedByTargetField($assocName) { throw new \BadMethodCallException('not implemented'); } public function getIdentifierValues($object) { throw new \BadMethodCallException('not implemented'); } } 