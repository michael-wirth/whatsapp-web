<?php
 namespace Doctrine\Tests; use Doctrine\Common\Collections\Collection; use Doctrine\Common\Collections\AbstractLazyCollection; use Doctrine\Common\Collections\ArrayCollection; class LazyArrayCollection extends AbstractLazyCollection { private $collectionOnInitialization; public function __construct(Collection $collection) { $this->collectionOnInitialization = $collection; } protected function doInitialize() { $this->collection = $this->collectionOnInitialization; } } 