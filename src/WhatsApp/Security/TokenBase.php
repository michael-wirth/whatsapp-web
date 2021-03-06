<?php

namespace WhatsApp\Security;
abstract class TokenBase
{
    protected $type = null;
    protected $logger;

    public function __construct($type, $logger)
    {
        $this->type = $type;
        $this->logger = $logger;
    }

    abstract public function generateToken($payload);

    abstract public function validateToken($receivedToken);
} 