<?php

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Constants;

function getMonologLevel($logLevel)
{
    switch ($logLevel) {
        case "debug":
            return Logger::DEBUG;
        case "info":
            return Logger::INFO;
        case "warn":
        case "warning":
            return Logger::WARNING;
        case "error":
            return Logger::ERROR;
        default:
            return Logger::INFO;
    }
}

$app->after(function (Request $request, Response $response, Application $app) {
    if ($app[Constants::INTERNAL_REQUEST_IDS_HTTP_HEADER] !== null) {
        $response->headers->set(Constants::INTERNAL_REQUEST_IDS_HTTP_HEADER, $app[Constants::INTERNAL_REQUEST_IDS_HTTP_HEADER]);
        $app['monolog']->info("Internal Request Ids: " . $app[Constants::INTERNAL_REQUEST_IDS_HTTP_HEADER]);
    }
}, Application::LATE_EVENT + 1);
$app->register(new Silex\Provider\MonologServiceProvider(), array('monolog.logfile' => $app['config']['app']['log']['file'], 'monolog.level' => getMonologLevel($app['config']['app']['log']['level'])));
function setLogFormat($app)
{
    $dateFormat = "Y-m-d H:i:s.u";
    $output = "[%datetime%] %channel%.%level_name%: [" . $app[Constants::REQUEST_ID_FIELD] . "] %message% %context% %extra%\n";
    $formatter = new LineFormatter($output, $dateFormat);
    $hdlrs = $app['monolog']->getHandlers();
    $hdlrs[0]->setFormatter($formatter);
}

setLogFormat($app);