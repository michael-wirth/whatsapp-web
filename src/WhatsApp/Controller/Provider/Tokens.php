<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Tokens implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $tokens = $app["controllers_factory"];
        $tokens->post("/", "WhatsApp\\Controller\\TokenController::createToken");
        $tokens->delete("/{token}", "WhatsApp\\Controller\\TokenController::deleteToken");
        return $tokens;
    }
} 