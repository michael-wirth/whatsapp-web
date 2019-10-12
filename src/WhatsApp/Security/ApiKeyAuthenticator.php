<?php

namespace WhatsApp\Security;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Util;
use WhatsApp\Constants;

class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{
    private $app;
    private $logger;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->logger = $app['monolog'];
        $this->apiKeyToken = getenv('WA_API_KEY');
        $apiKeyLen = strlen($this->apiKeyToken);
        if ($apiKeyLen < Constants::API_KEY_MIN_LENGTH || $apiKeyLen > Constants::API_KEY_MAX_LENGTH) {
            $this->apiKeyToken = '';
        }
    }

    public function getCredentials(Request $request)
    {
        if (strpos($request->getPathInfo(), '/v1/health') != 0) {
            return null;
        }
        if (!$auth_token = $request->headers->get(Constants::TOKEN_FIELD)) {
            $this->logger->warning("Missing authentication token");
            return null;
        }
        $this->logger->debug("auth_token=" . $auth_token);
        $tokens = explode(' ', $auth_token);
        if ($tokens[0] !== constants::API_KEY_TOKEN_SCHEMA || sizeof($tokens) < 2) {
            return null;
        }
        return array('username' => Constants::API_KEY_USER_NAME, 'token' => $tokens[1],);
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return new User($credentials['username'], $credentials['token'], array('ROLE_API'));
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $token = $credentials['token'];
        if ($user->getUsername() === Constants::API_KEY_USER_NAME) {
            return ($token === $this->apiKeyToken);
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $errors = new ApiErrors();
        $errors->add(ApiError::ACCESS_DENIED, strtr($exception->getMessageKey(), $exception->getMessageData()));
        $post = Util::genResponse(null, null, $errors, $this->app);
        return new JsonResponse($post, Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $errors = new ApiErrors();
        $errors->add(ApiError::ACCESS_DENIED, 'Missing or invalid authentication credentials');
        $post = Util::genResponse(null, null, $errors, $this->app);
        return new JsonResponse($post, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
} 