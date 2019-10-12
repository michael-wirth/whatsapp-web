<?php

namespace WhatsApp\Security;

use WhatsApp\Common\Util;

class JWTHelper
{
    const HMAC_ALGORITHM = 'sha256';
    const TOKEN_HEADER = '{"alg": "HS256", "typ": "JWT"}';
    private $header;
    private $cryptoKey;

    public function __construct($cryptoKey)
    {
        $this->header = Util::base64UrlEncode(self::TOKEN_HEADER);
        $this->cryptoKey = $cryptoKey;
    }

    public function generateToken($payload)
    {
        $payload = Util::base64UrlEncode(json_encode($payload));
        $signature = $this->generateSignature($this->header, $payload, $this->cryptoKey);
        return $this->header . '.' . $payload . '.' . $signature;
    }

    private function generateSignature($header, $payload)
    {
        return Util::base64UrlEncode(hash_hmac(self::HMAC_ALGORITHM, $header . '.' . $payload, $this->cryptoKey, true));
    }

    public function isValidFormat($receivedToken)
    {
        $values = explode('.', $receivedToken);
        return (count($values) == 3);
    }

    public function isValidSignature($header, $payload, $receivedSignature)
    {
        $expectedSignature = $this->generateSignature($header, $payload);
        return hash_equals($expectedSignature, $receivedSignature);
    }
} 