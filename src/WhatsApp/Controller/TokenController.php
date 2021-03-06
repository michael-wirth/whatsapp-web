<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Util;
use WhatsApp\Constants;
use WhatsApp\Security\JWTTokenBase;
use WhatsApp\Security\TokenizerFactory;
use WhatsApp\Security\TokenStorage;
use WhatsApp\Security\TokenType;

class TokenController
{
    public function createToken(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        $tokens = new \stdClass;
        do {
            $username = $this->getUsername($request, $app);
            if (is_null($username)) {
                $errors->add(ApiError::NOT_FOUND, "User does not exist");
                $respCode = Response::HTTP_NOT_FOUND;
                break;
            }
            $tokenStore = $this->getTokenStorage($app);
            if ($this->hasLongLivedToken($tokenStore, $username)) {
                $errors->add(ApiError::EXISTS, "Long lived token already exists");
                $respCode = Response::HTTP_CONFLICT;
                break;
            }
            try {
                $tokenizer = null;
                $currentTime = time();
                $tokenPayload = JWTTokenBase::createPayload($username, $currentTime);
                $tokenizer = TokenizerFactory::getTokenizer(TokenType::LONG, $app);
                $token = $tokenizer->generateToken($tokenPayload);
                $tokenStore->storeToken($token, $username);
                $tokens->token = $token;
                $currentTimeData = new \DateTime("@$currentTime");
                $tokens->created_at = $currentTimeData->format('Y-m-d H:i:sP');
                $payload->tokens = array($tokens);
            } catch (DBALException $e) {
                $errors->add(ApiError::INTERNAL_ERROR, "Unable to create token");
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                $app['monolog']->warning($e->getMessage());
            }
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    private function getUsername(Request $request, Application $app)
    {
        if (!$auth_token = $request->headers->get(Constants::TOKEN_FIELD)) {
            $app['monolog']->warning("Missing authentication token");
            return null;
        }
        $app['monolog']->debug("auth_token=" . $auth_token);
        $tokens = explode(' ', $auth_token);
        if ($tokens[0] !== Constants::TOKEN_SCHEMA) {
            $app['monolog']->error("Invalid authentication token format");
            return null;
        }
        return JWTTokenBase::getUsername($tokens[1]);
    }

    private function getTokenStorage(Application $app)
    {
        return $tokenStore = new TokenStorage($app['db'], Constants::TABLE_TOKENS, $app['monolog']);
    }

    private function hasLongLivedToken($tokenStore, $username)
    {
        $results = $tokenStore->getTokensForUser($username);
        foreach ($results as $result) {
            if (JWTTokenBase::getTokenType($result[Constants::KEY_TOKEN]) === TokenType::LONG) {
                return true;
            }
        }
        return false;
    }

    public function deleteToken(Request $request, Application $app, $token)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $tokens = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $tokenType = JWTTokenBase::getTokenType($token);
            if ($tokenType === null) {
                $errors->add(ApiError::INTERNAL_ERROR, "Invalid token type");
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            if ($tokenType === TokenType::SHORT) {
                $errors->add(ApiError::SERVICE_NOT_READY, "Short lived token(s) deletion not supported");
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $tokenStore = $this->getTokenStorage($app);
            $username = $this->getUsername($request, $app);
            if (!$this->isUserAdmin($username, $app) && !$tokenStore->userOwnsToken($token, $username)) {
                $errors->add(ApiError::ACCESS_DENIED, "Can not delete token");
                $respCode = Response::HTTP_UNAUTHORIZED;
                break;
            }
            try {
                $db_response = $tokenStore->deleteToken($token);
                if ($db_response == 0) {
                    $errors->add(ApiError::NOT_FOUND, "Token does not exist");
                    $respCode = Response::HTTP_NOT_FOUND;
                    break;
                }
                $tokens->token = $token;
                $payload->tokens = array($tokens);
            } catch (DBALException $e) {
                $errors->add(ApiError::INTERNAL_ERROR, "Unable to delete token");
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                $app['monolog']->warning($e->getMessage());
            }
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    private function isUserAdmin($username, Application $app)
    {
        return $username === Constants::USER_ADMIN && $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
    }
} 