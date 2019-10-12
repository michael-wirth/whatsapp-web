<?php
 namespace Symfony\Component\PropertyAccess; interface PropertyPathIteratorInterface extends \Iterator, \SeekableIterator { public function isIndex(); public function isProperty(); } 