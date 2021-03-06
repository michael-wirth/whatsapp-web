<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Connector;
use WhatsApp\Common\RateLimiter;
use WhatsApp\Common\Util;
use WhatsApp\Constants;

class HealthController
{
    public function getHealth(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            if (RateLimiter::increase("healthCheck", Constants::RL_MAX_HEALTH_CHECKS[0], Constants::RL_MAX_HEALTH_CHECKS[1], "health") <= 0) {
                $app['monolog']->info("Rate limiting exceeded on health check: ");
                $errors->add(ApiError::TOO_MANY_REQUESTS, "Rate limiting exceeded on health check");
                $respCode = Response::HTTP_TOO_MANY_REQUESTS;
                break;
            }
            if (Util::isMultiConnect()) {
                $response = Connector::check_health_multiconnect($app, "gateway_status", $errors);
            } else {
                $response = Connector::send_receive($app, array('gateway_status' => Null), 'control', $errors, $app['monolog']);
            }
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }
} 