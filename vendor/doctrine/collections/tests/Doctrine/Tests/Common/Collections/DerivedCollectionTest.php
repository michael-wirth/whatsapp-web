<?php
 namespace Doctrine\Tests\Common\Collections; use Doctrine\Tests\DerivedArrayCollection; class DerivedCollectionTest { public function testDerivedClassCreation() { $collection = new DerivedArrayCollection(new \stdClass()); $closure = function () { return $allMatches = false; }; self::assertInstanceOf(DerivedArrayCollection::class, $collection->map($closure)); self::assertInstanceOf(DerivedArrayCollection::class, $collection->filter($closure)); self::assertContainsOnlyInstancesOf(DerivedArrayCollection::class, $collection->partition($closure)); self::assertInstanceOf(DerivedArrayCollection::class, $collection->matching(new Criteria())); } } 