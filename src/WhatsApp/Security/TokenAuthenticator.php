<?php

namespace WhatsApp\Security;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Util;
use WhatsApp\Constants;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private $encoderFactory;
    private $app;
    private $logger;
    private $tokenizer;
    private $tokenStore;

    public function __construct(EncoderFactory $encoderFactory, Application $app)
    {
        $this->encoderFactory = $encoderFactory;
        $this->app = $app;
        $this->logger = $app['monolog'];
        $this->tokenStore = new TokenStorage($app['db'], Constants::TABLE_TOKENS, $this->logger);
    }

    public function getCredentials(Request $request)
    {
        #Util::printObject($this->logger, 'request', $request->headers);

        if (!$auth_token = $request->headers->get(Constants::TOKEN_FIELD)) {
            $this->logger->warning("Missing authentication token");
            return;
        }
        $this->logger->debug("auth_token=" . $auth_token);
        $tokens = explode(' ', $auth_token);
        if ($tokens[0] !== Constants::TOKEN_SCHEMA) {
            $this->logger->error("Invalid authentication token format");
            return;
        }
        $tokenType = JWTTokenBase::getTokenType($tokens[1]);
        if ($tokenType === null) {
            $this->logger->error("Invalide token type");
            return;
        }
        $this->tokenizer = TokenizerFactory::getTokenizer($tokenType, $this->app);
        $username = JWTTokenBase::getUsername($tokens[1]);
        return array('username' => $username, 'token' => $tokens[1],);
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['username']);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $token = $credentials['token'];
        $valid = false;
        do {
            $valid = $this->tokenizer->validateToken($token, $this->app);
            if (!$valid) {
                $this->tokenStore->deleteToken($token);
                break;
            }
            $dbToken = $this->tokenStore->getToken($token);
            if (is_null($dbToken)) {
                $this->logger->info("Authentication token " . $token . " revoked");
                $valid = false;
            }
        } while (false);
        return $valid;
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
