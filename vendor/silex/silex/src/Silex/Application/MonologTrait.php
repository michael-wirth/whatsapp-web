<?php
 namespace Silex\Application; use Monolog\Logger; trait MonologTrait { public function log($message, array $context = array(), $level = Logger::INFO) { return $this['monolog']->addRecord($level, $message, $context); } } 