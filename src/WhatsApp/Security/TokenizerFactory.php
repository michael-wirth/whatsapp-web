<?php

namespace WhatsApp\Security;

use Silex\Application;

class TokenizerFactory
{
    public static function getTokenizer($type, Application $app)
    {
        switch ($type) {
            case TokenType::LONG:
                return new LongLivedToken($app['monolog']);
            case TokenType::SHORT:
            default:
                return new ShortLivedToken($app['monolog']);
        }
    }
} 