<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Media implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $media = $app["controllers_factory"];
        $media->post("/", "WhatsApp\\Controller\\MediaController::upload");
        $media->delete("/{id}", "WhatsApp\\Controller\\MediaController::remove");
        $media->get("/{id}", "WhatsApp\\Controller\\MediaController::download");
        return $media;
    }
} 