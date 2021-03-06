<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Certificates implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $certs = $app["controllers_factory"];
        $certs->post("/external", "WhatsApp\\Controller\\CertificateController::upload");
        $certs->get("/external/ca", "WhatsApp\\Controller\\CertificateController::downloadCA");
        $certs->post("/webhooks/ca", "WhatsApp\\Controller\CertificateController::setWebhookCaCerts");
        $certs->get("/webhooks/ca", "WhatsApp\\Controller\CertificateController::getWebhookCaCerts");
        $certs->delete("/webhooks/ca", "WhatsApp\\Controller\CertificateController::deleteWebhookCaCerts");
        return $certs;
    }
} 