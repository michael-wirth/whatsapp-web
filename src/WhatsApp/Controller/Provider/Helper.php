<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Helper implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        return $this->connectV1($app);
    }

    public function connectV1(Application $app)
    {
        $prefix = "/v1";
        $helper = $app["controllers_factory"];
        $helper->get("/metrics", "WhatsApp\\Controller\\MetricsController::getMetrics");
        $helper->post($prefix . "/test", "WhatsApp\\Controller\\HelperController::test");
        $helper->post($prefix . "/test2", "WhatsApp\\Controller\\HelperController::test2");
        $helper->post($prefix . "/account", "WhatsApp\\Controller\AccountController::codeRequest");
        $helper->post($prefix . "/media", "WhatsApp\\Controller\\MediaController::upload");
        $helper->post($prefix . "/messages", "WhatsApp\\Controller\\MessagesController::send");
        $helper->post($prefix . "/users", "WhatsApp\\Controller\\UsersController::createUser");
        $helper->post($prefix . "/contacts", "WhatsApp\\Controller\\ContactsController::checkContacts");
        $helper->get($prefix . "/groups", "WhatsApp\\Controller\\GroupsController::getAllGroups");
        $helper->post($prefix . "/groups", "WhatsApp\\Controller\\GroupsController::createGroup");
        $helper->get($prefix . "/health", "WhatsApp\\Controller\\HealthController::getHealth");
        $helper->get($prefix . "/support", "WhatsApp\\Controller\\SupportController::getInfo");
        return $helper;
    }
} 