<?php
 namespace Doctrine\Tests\Common\Annotations\Fixtures; use Doctrine\Tests\Common\Annotations\Fixtures\Annotation\Template; use Doctrine\Tests\Common\Annotations\Fixtures\Annotation\Route; use Doctrine\Tests\Common\Annotations\Fixtures\Traits\SecretRouteTrait; class ControllerWithTrait { use SecretRouteTrait; public function indexAction() { return array(); } } 