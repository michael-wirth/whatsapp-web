<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Util;

class HelperController
{
    public function test(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function test2(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_BAD_REQUEST;
        $logger = $app['monolog'];
        $errors->add(ApiError::MESSAGE_TOO_LONG, "Message is > 4096 bytes");
        $errors->add(ApiError::INVALID_RECIPIENT_TYPE, "Recipient must be individual or group only", "http://www.whatsapp.net");
        $errors->add(ApiError::NOT_GROUP_PARTICIPANT);
        $logger->info($errors->toJson());
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }
}