<?php
 namespace Silex\Api; use Silex\Application; interface BootableProviderInterface { public function boot(Application $app); } 