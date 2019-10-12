<?php

namespace WhatsApp\Security;

use WhatsApp\Common\Util;

class JWTTokenBase extends TokenBase
{
    const CRYPTO_KEY = 'V2hhdDVBcHBFbnRlcnByaTUzQzFpZW50SE1BQ1NlY3IzdAo=';
    private $jwtHelper;

    public function __construct($type, $logger)
    {
        parent::__construct($type, $logger);
        $this->jwtHelper = new JWTHelper(base64_decode(self::CRYPTO_KEY));
    }

    public static function getUsername($receivedToken)
    {
        $payload = self::getPayload($receivedToken);
        return $payload->user;
    }

    private static function getPayload($receivedToken)
    {
        $values = explode('.', $receivedToken);
        return json_decode(Util::base64UrlDecode($values[1]));
    }

    public static function createPayload($user, $iat, $exp = -1)
    {
        return array('user' => $user, 'iat' => $iat, 'exp' => $exp,);
    }

    public static function getTokenType($receivedToken)
    {
        $payload = self::getPayload($receivedToken);
        if (!isset($payload->exp)) {
            return null;
        }
        return ($payload->exp == -1) ? TokenType::LONG : TokenType::SHORT;
    }

    public function generateToken($payload)
    {
        $payload['wa:rand'] = random_int(PHP_INT_MIN, PHP_INT_MAX);
        return $this->jwtHelper->generateToken($payload);
    }

    public function validateToken($receivedToken)
    {
        $valid = false;
        do {
            if (!$this->jwtHelper->isValidFormat($receivedToken)) {
                $this->logger->error("Invalid token format:" . $receivedToken);
                break;
            }
            $values = explode('.', $receivedToken);
            $valid = $this->jwtHelper->isValidSignature($values[0], $values[1], $values[2]);
        } while (false);
        return $valid;
    }
} 