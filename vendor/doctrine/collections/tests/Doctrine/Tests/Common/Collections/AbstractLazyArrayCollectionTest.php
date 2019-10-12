<?php
 namespace Doctrine\Tests\Common\Collections; use Doctrine\Common\Collections\ArrayCollection; use Doctrine\Tests\LazyArrayCollection; class AbstractLazyCollectionTest extends BaseArrayCollectionTest { protected function buildCollection(array $elements = []) { return new LazyArrayCollection(new ArrayCollection($elements)); } public function testLazyCollection() { $collection = $this->buildCollection(['a', 'b', 'c']); $this->assertFalse($collection->isInitialized()); $this->assertCount(3, $collection); } } 