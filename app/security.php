<?php
use WhatsApp\Security\UserProvider;
use WhatsApp\Security\ApiKeyAuthenticator;
use WhatsApp\Security\TokenAuthenticator;
use WhatsApp\Common\RateLimiter;
use WhatsApp\Constants;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
$app['app.token_authenticator'] = function () use ($app) {
    return new TokenAuthenticator($app['security.encoder_factory'], $app);
};
$app['app.apikey_authenticator'] = function () use ($app) {
    return new ApiKeyAuthenticator($app);
};
$app['security.firewalls'] = array('login' => array('pattern' => '^/v1/users/login$', 'http' => true, 'users' => function () use ($app) {
    $app[Constants::REQUEST_PATH] = "POST_v1_users_login";
    return new UserProvider($app['db']);
},), 'token' => array('pattern' => '^.*$', 'guard' => array('authenticators' => array('app.apikey_authenticator', 'app.token_authenticator'), 'entry_point' => 'app.apikey_authenticator',), 'users' => function () use ($app) {
    return new UserProvider($app['db']);
},),);
$app['security.role_hierarchy'] = array('ROLE_USER' => array('ROLE_API'), 'ROLE_ADMIN' => array('ROLE_USER'),);
$app['security.access_rules'] = array(array('^/v1/admin', 'ROLE_ADMIN'), array('^/v1/account', 'ROLE_ADMIN'), array('^/v1/settings', 'ROLE_ADMIN'), array(new RequestMatcher('/v1/users/log', null, ['POST']), 'ROLE_USER'), array(new RequestMatcher('/v1/users', null, ['POST', 'DELETE']), 'ROLE_ADMIN'), array(new RequestMatcher('/v1/certificates', null, ['POST', 'DELETE']), 'ROLE_ADMIN'), array(new RequestMatcher('/v1/certificates/webhooks', null, ['GET']), 'ROLE_ADMIN'), array(new RequestMatcher('/v1/health', null, ['GET']), 'ROLE_API'), array('^.*$', 'ROLE_USER'),);
$app->after(function (Request $request, Response $response, Application $app) {
    if ($response->getStatusCode() == Response::HTTP_UNAUTHORIZED) {
        RateLimiter::onUnauthorizedResponse($request, $response, $app);
    }
    $response->headers->set(Constants::REQUEST_ID_HTTP_HEADER, $app[Constants::REQUEST_ID_FIELD]);
});
$app->register(new Silex\Provider\SecurityServiceProvider(), array('security.firewalls' => $app['security.firewalls'], 'security.role_hierarchy' => $app['security.role_hierarchy'], 'security.access_rules' => $app['security.access_rules'],));
$app->boot();
