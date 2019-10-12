<?php

namespace WhatsApp\Common;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Touhonoob\RateLimit\Adapter\APCu as RateLimitAdapterAPCu;
use Touhonoob\RateLimit\RateLimit;
use WhatsApp\Constants;

class RateLimiter
{
    public static function check(string $name, int $maxRequests, int $period, string $id)
    {
        $adapter = new RateLimitAdapterAPCu();
        $rateLimit = new RateLimit($name, $maxRequests, $period, $adapter);
        return $rateLimit->getAllow($id);
    }

    public static function onUnauthorizedResponse(Request $request, Response $response, Application $app)
    {
        $clientIP = $request->getClientIp();
        $errors = new ApiErrors();
        if ($request->getPathInfo() === '/v1/users/login') {
            if (self::increase("unauthorizedLogin", Constants::RL_MAX_WRONG_LOGINS[0], Constants::RL_MAX_WRONG_LOGINS[1], $clientIP) <= 0) {
                $app['monolog']->info("Rate limiting exceeded on bad login attempts: " . $clientIP);
                $errors->add(ApiError::TOO_MANY_REQUESTS, "Rate limiting exceeded on bad login attempts");
                $response->setStatusCode(Response::HTTP_TOO_MANY_REQUESTS);
                $resp = Util::genResponse(null, new \Stdclass, $errors, $app);
                $response->setContent(json_encode($resp));
            }
        }
    }

    public static function increase(string $name, int $maxRequests, int $period, string $id)
    {
        $adapter = new RateLimitAdapterAPCu();
        $rateLimit = new RateLimit($name, $maxRequests, $period, $adapter);
        return $rateLimit->check($id);
    }
}