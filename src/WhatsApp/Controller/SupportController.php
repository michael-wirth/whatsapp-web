<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Connector;
use WhatsApp\Common\Util;

class SupportController
{
    public function getInfo(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            if (Util::isMultiConnect()) {
                $response = Connector::check_health_multiconnect($app, "support_info", $errors);
            } else {
                $response = Connector::send_receive($app, array('support_info' => Null), 'control', $errors, $app['monolog']);
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