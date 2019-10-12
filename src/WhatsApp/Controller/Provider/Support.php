<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Support implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $support = $app["controllers_factory"];
        $support->get("/", "WhatsApp\\Controller\\SupportController::getInfo");
        return $support;
    }
} 