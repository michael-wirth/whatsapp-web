<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Users implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $users = $app["controllers_factory"];
        $users->post("/login", "WhatsApp\\Controller\\UsersController::login");
        $users->post("/logout", "WhatsApp\\Controller\\UsersController::logout");
        $users->post("/", "WhatsApp\\Controller\\UsersController::createUser");
        $users->put("/{username}", "WhatsApp\\Controller\\UsersController::updateUser");
        $users->delete("/{username}", "WhatsApp\\Controller\\UsersController::deleteUser");
        $users->get("/{username}", "WhatsApp\\Controller\\UsersController::getUserByName");
        return $users;
    }
} 