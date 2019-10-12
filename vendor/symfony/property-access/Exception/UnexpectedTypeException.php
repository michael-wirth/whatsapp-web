<?php
 namespace Symfony\Component\PropertyAccess\Exception; use Symfony\Component\PropertyAccess\PropertyPathInterface; class UnexpectedTypeException extends RuntimeException { public function __construct($value, PropertyPathInterface $path, $pathIndex) { $message = sprintf( 'PropertyAccessor requires a graph of objects or arrays to operate on, '. 'but it found type "%s" while trying to traverse path "%s" at property "%s".', gettype($value), (string) $path, $path->getElement($pathIndex) ); parent::__construct($message); } } 