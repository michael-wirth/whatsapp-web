<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Messages implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $users = $app["controllers_factory"];
        $users->post("/", "WhatsApp\\Controller\\MessagesController::send");
        $users->delete("/", "WhatsApp\\Controller\\SettingsController::deleteMessages");
        $users->delete("/{id}", "WhatsApp\\Controller\\MessagesController::delete");
        $users->get("/{id}", "WhatsApp\\Controller\\MessagesController::get");
        $users->put("/{id}", "WhatsApp\\Controller\\MessagesController::updateStatus");
        return $users;
    }
} 