<?php

namespace WhatsApp\Security;
class LongLivedToken extends JWTTokenBase
{
    public function __construct($logger)
    {
        parent::__construct(TokenType::LONG, $logger);
    }

    public function validateToken($receivedToken)
    {
        return parent::validateToken($receivedToken);
    }
} 