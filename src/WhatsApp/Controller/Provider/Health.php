<?php
namespace WhatsApp\Controller\Provider; use Silex\Application; use Silex\Api\ControllerProviderInterface; class Health implements ControllerProviderInterface { public function connect(Application $app) { $health = $app["controllers_factory"]; $health->get("/", "WhatsApp\\Controller\\HealthController::getHealth"); return $health; } } 