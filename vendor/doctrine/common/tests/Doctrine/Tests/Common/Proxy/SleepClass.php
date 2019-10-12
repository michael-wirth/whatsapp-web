<?php
 namespace Doctrine\Tests\Common\Proxy; class SleepClass { public $id; public function __sleep() { return ['id']; } } 