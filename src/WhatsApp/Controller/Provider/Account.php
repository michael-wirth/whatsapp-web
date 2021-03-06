<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Account implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $account = $app["controllers_factory"];
        $account->post("/", "WhatsApp\\Controller\AccountController::codeRequest");
        $account->post("/verify", "WhatsApp\\Controller\AccountController::register");
        $account->post("/shards", "WhatsApp\\Controller\AccountController::setShards");
        $account->get("/shards", "WhatsApp\\Controller\AccountController::getShards");
        return $account;
    }
} 