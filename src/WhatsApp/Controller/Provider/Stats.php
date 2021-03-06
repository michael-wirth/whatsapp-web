<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Stats implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $stats = $app["controllers_factory"];
        $stats->get("/db", "WhatsApp\\Controller\\StatsController::getDBStats");
        $stats->get("/db/internal", "WhatsApp\\Controller\\StatsController::getDBInternalStats");
        $stats->get("/app", "WhatsApp\\Controller\\StatsController::getAppStats");
        $stats->get("/app/internal", "WhatsApp\\Controller\\StatsController::getAppInternalStats");
        return $stats;
    }
} 