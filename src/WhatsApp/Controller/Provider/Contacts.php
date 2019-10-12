<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Contacts implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $contacts = $app["controllers_factory"];
        $contacts->post("/", "WhatsApp\\Controller\\ContactsController::checkContacts");
        return $contacts;
    }
} 