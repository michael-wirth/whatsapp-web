<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Health implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $health = $app["controllers_factory"];
        $health->get("/", "WhatsApp\\Controller\\HealthController::getHealth");
        return $health;
    }
} 