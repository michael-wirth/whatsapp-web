<?php

namespace WhatsApp\Security;

use WhatsApp\Common\Util;

class ShortLivedToken extends JWTTokenBase
{
    const CLOCK_SKEW = 600;

    public function __construct($logger)
    {
        parent::__construct(TokenType::SHORT, $logger);
    }

    public function validateToken($receivedToken)
    {
        if (!parent::validateToken($receivedToken)) {
            return false;
        }
        return $this->isActive($receivedToken);
    }

    private function isActive($receivedToken)
    {
        $values = explode('.', $receivedToken);
        $payload = json_decode(Util::base64UrlDecode($values[1]));
        if (!isset($payload->exp)) {
            $this->logger->error("Invalid token no exp");
            return false;
        }
        $exp = $payload->exp;
        if ($payload->exp < (time() - self::CLOCK_SKEW)) {
            $this->logger->error("Token expired: " . $receivedToken);
            $this->logger->error("Token expiry=" . $payload->exp . ", currentTime=" . time());
            return false;
        }
        return true;
    }
} 