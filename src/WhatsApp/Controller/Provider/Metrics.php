<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Metrics implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $metrics = $app["controllers_factory"];
        $metrics->get("/", "WhatsApp\\Controller\\MetricsController::getMetrics");
        return $metrics;
    }
} 