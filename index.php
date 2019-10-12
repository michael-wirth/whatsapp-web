<?php
require_once __DIR__ . '/vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\RateLimiter;
use WhatsApp\Common\Util;
use WhatsApp\Constants;
use WhatsApp\Metrics\MetricsStorage;

$app = new Silex\Application();
$app['debug'] = (getenv('WA_ENT_ENV') === 'dev') ? true : false;
mt_srand(crc32(microtime()));
$app[Constants::REQUEST_ID_FIELD] = str_replace('-', '', Util::getUUid());
$app[Constants::INTERNAL_REQUEST_IDS_HTTP_HEADER] = null;
$app[Constants::REQUEST_PATH] = "unknown";
$app[Constants::API_METRICS] = new MetricsStorage();
try {
    require_once __DIR__ . '/app/routes.php';
    require_once __DIR__ . '/app/config.php';
    require_once __DIR__ . '/app/logger.php';
    require_once __DIR__ . '/app/db.php';
    require_once __DIR__ . '/app/security.php';
    require_once __DIR__ . '/app/metrics.php';
} catch (\Exception $e) {
    $Exception = $e->getMessage();
    $val = $e->getCode();
    $app->before(function () use ($Exception, $val) {
        throw new \Exception($Exception, $val);
    });
}
$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    $errors = new ApiErrors();
    $payload = new \stdClass;
    $respCode = $code;
    $errorCode = $e->getCode();
    switch ($code) {
        case 404:
            $errors->add(ApiError::NOT_FOUND, "URL path not found");
            break;
        case 403:
            $errors->add(ApiError::ACCESS_DENIED, "User doesn't have permission");
            break;
        default:
            if ($errorCode === 2002) {
                $errors->add(ApiError::INTERNAL_ERROR, "Can't connect to the Database server through socket");
            } else {
                $errors->add(ApiError::INTERNAL_ERROR);
            }
    }
    $post = Util::genResponse(null, $payload, $errors, $app);
    return $app->json($post, $respCode);
});
$app->before(function (Request $request) use ($app) {
    $request_id = $request
        ->headers
        ->get(Constants::REQUEST_ID_HTTP_HEADER);
    if ($request_id !== null && strlen($request_id) > 0) {
        $app[Constants::REQUEST_ID_FIELD] = $request_id;
        setLogFormat($app);
    }
}
    , Application::EARLY_EVENT);
$app->before(function (Request $request) use ($app) {
    if (0 === strpos($request
            ->headers
            ->get('Content-Type'), 'application/json')) {
        $content = $request->getContent();
        if (!strlen($content)) {
            return;
        }
        $data = json_decode($request->getContent(), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $request
                ->request
                ->replace(is_array($data) ? $data : array());
        } else {
            $errorMsg = "Unable to decode the request: " . json_last_error_msg();
            $payload = new \stdClass;
            $respCode = Response::HTTP_BAD_REQUEST;
            $errors = new ApiErrors();
            $errors->add(ApiError::INVALID_REQUEST, $errorMsg);
            $post = Util::genResponse(null, $payload, $errors, $app);
            return $app->json($post, $respCode);
        }
    }
    if (Util::isSandboxMode()) {
        if (RateLimiter::increase("sandboxApiRequests", Constants::RL_MAX_SB_API_REQUESTS[0], Constants::RL_MAX_SB_API_REQUESTS[1], "global") <= 0) {
            $payload = new \stdClass;
            $errors = new ApiErrors();
            $errorMsg = "Sandbox mode: API request rate limit exceeded";
            $app['monolog']->info($errorMsg);
            $respCode = Response::HTTP_TOO_MANY_REQUESTS;
            $errors->add(ApiError::TOO_MANY_REQUESTS, $errorMsg);
            $post = Util::genResponse(null, $payload, $errors, $app);
            return $app->json($post, $respCode);
        }
    }
});
$app->run(); ?>
