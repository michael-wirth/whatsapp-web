<?php

namespace WhatsApp\Controller;

use Doctrine\DBAL\DBALException;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\RateLimiter;
use WhatsApp\Common\Util;
use WhatsApp\Constants;
use WhatsApp\Security\JWTTokenBase;
use WhatsApp\Security\TokenizerFactory;
use WhatsApp\Security\TokenStorage;
use WhatsApp\Security\TokenType;
use WhatsApp\User\UserStorage;

class UsersController
{
    public function createUser(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $users = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_CREATED;
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        do {
            $clientIP = $request->getClientIp();
            if (RateLimiter::increase("createUpdateUser", Constants::RL_MAX_REQUESTS_TIME[0], Constants::RL_MAX_REQUESTS_TIME[1], $clientIP) <= 0) {
                $app['monolog']->info("Rate limiting exceeded on create user: " . $clientIP);
                $errors->add(ApiError::TOO_MANY_REQUESTS, "Rate limiting exceeded on create user");
                $respCode = Response::HTTP_TOO_MANY_REQUESTS;
                break;
            }
            if ($username === Constants::API_KEY_USER_NAME) {
                $app['monolog']->info("Username prohibited", [$username]);
                $errors->add(ApiError::PARAMETER_INVALID, "Invalid Username");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $len = strlen($username);
            if (($len > Constants::MAX_USERNAME_LENGTH) || ($len < Constants::MIN_USERNAME_LENGTH)) {
                $app['monolog']->info("Username doesn't meet length requirements", [$len]);
                $errors->add(ApiError::PARAMETER_INVALID, "Username doesn't meet length requirements");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $encodedUsername = urlencode($username);
            if ($encodedUsername !== $username) {
                $app['monolog']->info("Username contains prohibited characters");
                $errors->add(ApiError::PARAMETER_INVALID, "Username contains prohibited characters");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $len = strlen($password);
            if (($len > Constants::MAX_PASSWORD_LENGTH) || ($len < Constants::MIN_PASSWORD_LENGTH)) {
                $app['monolog']->info("Password doesn't meet length requirements", [$len]);
                $errors->add(ApiError::PARAMETER_INVALID, "Password doesn't meet length requirements");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            try {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $storage = new UserStorage($app['db'], Constants::TABLE_USERS, $app['monolog']);
                $storage->createUser($username, $password_hash, 'ROLE_USER');
                $response = array();
                $users->username = $username;
                $payload->users = array($users);
            } catch (DBALException $e) {
                $errors->add(ApiError::INTERNAL_ERROR, "Unable to create user. Already exist?");
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                $app['monolog']->warning($e->getMessage());
            }
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function updateUser(Request $request, Application $app, $username)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $users = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        $password = $request->request->get('password');
        do {
            $clientIP = $request->getClientIp();
            if (RateLimiter::increase("createUpdateUser", Constants::RL_MAX_REQUESTS_TIME[0], Constants::RL_MAX_REQUESTS_TIME[1], $clientIP) <= 0) {
                $app['monolog']->info("Rate limiting exceeded on update user: " . $clientIP);
                $errors->add(ApiError::TOO_MANY_REQUESTS, "Rate limiting exceeded on update user");
                $respCode = Response::HTTP_TOO_MANY_REQUESTS;
                break;
            }
            if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN') && ($username !== $app['user']->getUsername())) {
                $app['monolog']->info("Regular user cannot modify other user");
                $errors->add(ApiError::ACCESS_DENIED, "Regular user cannot modify other user");
                $respCode = Response::HTTP_FORBIDDEN;
                break;
            }
            if (Util::isSandboxMode()) {
                if ($username === Constants::SB_ADMIN_NAME && ($username !== $app['user']->getUsername())) {
                    $message = "Sandbox admin cannot be modified by other user";
                    $app['monolog']->info($message);
                    $errors->add(ApiError::ACCESS_DENIED, $message);
                    $respCode = Response::HTTP_FORBIDDEN;
                    break;
                }
            }
            try {
                if (($respCode = $this->updatePassword($app, $errors, $username, $password)) !== Response::HTTP_OK) {
                    break;
                }
                $users->username = $username;
                $payload->users = array($users);
            } catch (DBALException $e) {
                $errors->add(ApiError::INTERNAL_ERROR, "Unable to update user");
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                $app['monolog']->warning($e->getMessage());
            }
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function updatePassword($app, $errors, $username, $password)
    {
        $respCode = Response::HTTP_OK;
        do {
            if (Util::validatePassword($password) === false) {
                $e[0] = "Password doesn't meet complexity requirements";
                $e[1] = ": length between 8 and 64 characters, at least 1 each of upper-case character, lower-case character, digit, special character (" . Constants::PASSWORD_SPECIAL_CHARS . ") are required";
                $app['monolog']->info($e[0]);
                $errors->add(ApiError::PARAMETER_INVALID, $e[0] . $e[1]);
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $storage = new UserStorage($app['db'], Constants::TABLE_USERS, $app['monolog']);
            $db_response = $storage->updateUser($username, $password_hash);
            if ($db_response == 0) {
                $errors->add(ApiError::NOT_FOUND, "User does not exist");
                $respCode = Response::HTTP_NOT_FOUND;
                break;
            }
        } while (false);
        return $respCode;
    }

    public function deleteUser(Request $request, Application $app, $username)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $users = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            if ($username === Constants::USER_ADMIN || (Util::isSandboxMode() && $username === Constants::SB_ADMIN_NAME)) {
                $errors->add(ApiError::PARAMETER_INVALID, "Admin user cannot be deleted");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            try {
                $storage = new UserStorage($app['db'], Constants::TABLE_USERS, $app['monolog']);
                $db_response = $storage->deleteUser($username);
                if ($db_response == 0) {
                    $errors->add(ApiError::NOT_FOUND, "User does not exist");
                    $respCode = Response::HTTP_NOT_FOUND;
                    break;
                }
                $tokenStore = new TokenStorage($app['db'], Constants::TABLE_TOKENS, $app['monolog']);
                $tokenStore->deleteTokenByUsername($username);
                $users->username = $username;
                $payload->users = array($users);
            } catch (DBALException $e) {
                $errors->add(ApiError::INTERNAL_ERROR, "Unable to delete user");
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                $app['monolog']->warning($e->getMessage());
            }
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function getUserByName(Request $request, Application $app, $username)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $users = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN') && ($username !== $app['user']->getUsername())) {
                $app['monolog']->info("Regular user cannot read other user information");
                $errors->add(ApiError::ACCESS_DENIED, "Regular user cannot read other user information");
                $respCode = Response::HTTP_FORBIDDEN;
                break;
            }
            try {
                $storage = new UserStorage($app['db'], Constants::TABLE_USERS, $app['monolog']);
                $userData = $storage->getUser($username);
                if (is_null($userData)) {
                    $errors->add(ApiError::NOT_FOUND, "User does not exist");
                    $respCode = Response::HTTP_NOT_FOUND;
                } else {
                    $users = $userData;
                    $users->username = $username;
                    $payload->users = array($users);
                }
            } catch (DBALException $e) {
                $errors->add(ApiError::INTERNAL_ERROR, "Unable to retrieve user");
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                $app['monolog']->warning($e->getMessage());
            }
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function login(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $users = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        try {
            do {
                $clientIP = $request->getClientIp();
                $canProceed = RateLimiter::check("unauthorizedLogin", Constants::RL_MAX_WRONG_LOGINS[0], Constants::RL_MAX_WRONG_LOGINS[1], $clientIP);
                if ($canProceed <= 0) {
                    $app['monolog']->info("Rate limiting exceeded on bad login attempts: " . $clientIP);
                    $errors->add(ApiError::TOO_MANY_REQUESTS, "Rate limiting exceeded on bad login attempts");
                    $respCode = Response::HTTP_TOO_MANY_REQUESTS;
                    break;
                }
                $username = $request->headers->get('PHP_AUTH_USER');
                $newPassword = Util::getOptionalParam($request, $errors, 'new_password');
                if ($username === Constants::USER_ADMIN || (Util::isSandboxMode() && $username === Constants::SB_ADMIN_NAME)) {
                    if (($app['user']->getPassword() === Constants::ADMIN_PASSWORD) && !$newPassword) {
                        $errors->add(ApiError::PASSWORD_CHANGE_REQUIRED, "Password must be changed");
                        $respCode = Response::HTTP_UNAUTHORIZED;
                        break;
                    }
                }
                if ($newPassword) {
                    if (($respCode = $this->updatePassword($app, $errors, $username, $newPassword)) !== Response::HTTP_OK) {
                        break;
                    }
                }
                $currentTime = time();
                $expTs = $currentTime + $app['config']['app']['token']['lifetime'];
                $tokenizer = TokenizerFactory::getTokenizer(TokenType::SHORT, $app);
                $tokenStore = new TokenStorage($app['db'], Constants::TABLE_TOKENS, $app['monolog']);
                $tokenPayload = JWTTokenBase::createPayload($username, $currentTime, $expTs);
                $token = $tokenizer->generateToken($tokenPayload);
                $tokenStore->storeToken($token, $username);
                $expDate = new \DateTime("@$expTs");
                $users->token = $token;
                $users->expires_after = $expDate->format('Y-m-d H:i:sP');
                $payload->users = array($users);
            } while (false);
        } catch (DBALException $e) {
            $errors->add(ApiError::INTERNAL_ERROR, "Unable to create token");
            $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $app['monolog']->warning($e->getMessage());
        }
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function logout(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        try {
            $auth_token = $request->headers->get(Constants::TOKEN_FIELD);
            $tokens = explode(' ', $auth_token);
            $tokenStore = new TokenStorage($app['db'], Constants::TABLE_TOKENS, $app['monolog']);
            $tokenStore->deleteToken($tokens[1]);
            $response = array();
        } catch (DBALException $e) {
            $errors->add(ApiError::INTERNAL_ERROR, "Unable to delete token");
            $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $app['monolog']->warning($e->getMessage());
        }
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }
} 