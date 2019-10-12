<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Constants;
use WhatsApp\Metrics\GlobalMetricsType;

$app->after(function (Request $request, Response $response, Application $app) {
    $latency = (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000;
    $respCode = $response->getStatusCode();
    $app['monolog']->info("Request " . $request->getMethod() . "_" . $request->getPathInfo() . " returns " . $respCode . " in " . round($latency, 2) . " ms");
    $reqPath = $request->get("_route");
    if (is_null($reqPath)) {
        $reqPath = $app[Constants::REQUEST_PATH];
    }
    if (strlen($reqPath) > 1 && $reqPath[strlen($reqPath) - 1] === '_') {
        $reqPath = substr($reqPath, 0, -1);
    }
    $app[Constants::API_METRICS]->incDuration(GlobalMetricsType::API_REQUESTS, $latency, array($respCode));
    $app[Constants::API_METRICS]->persist($reqPath);
}, Application::LATE_EVENT); ?>
