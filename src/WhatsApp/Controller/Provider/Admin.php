<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Admin implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $admin = $app["controllers_factory"];
        $admin->post("/database/migrate", "WhatsApp\\Controller\AdminController::migrateDatabase");
        return $admin;
    }
} 