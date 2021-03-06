<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Settings implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $settings = $app["controllers_factory"];
        $settings->patch("/application", "WhatsApp\\Controller\\SettingsController::setAppSettings");
        $settings->get("/application", "WhatsApp\\Controller\\SettingsController::getAppSettings");
        $settings->delete("/application", "WhatsApp\\Controller\\SettingsController::deleteAppSettings");
        $settings->post("/business/profile", "WhatsApp\\Controller\\SettingsController::setBusinessProfile");
        $settings->get("/business/profile", "WhatsApp\\Controller\\SettingsController::getBusinessProfile");
        $settings->post("/profile/photo", "WhatsApp\\Controller\\SettingsController::setProfilePhoto");
        $settings->get("/profile/photo", "WhatsApp\\Controller\\SettingsController::getProfilePhoto");
        $settings->delete("/profile/photo", "WhatsApp\\Controller\\SettingsController::deleteProfilePhoto");
        $settings->patch("/profile/about", "WhatsApp\\Controller\\SettingsController::setAbout");
        $settings->get("/profile/about", "WhatsApp\\Controller\\SettingsController::getAbout");
        $settings->post("/account/two-step", "WhatsApp\\Controller\SettingsController::setTwoFac");
        $settings->delete("/account/two-step", "WhatsApp\\Controller\SettingsController::unsetTwoFac");
        $settings->post("/backup", "WhatsApp\\Controller\SettingsController::backup");
        $settings->post("/restore", "WhatsApp\\Controller\SettingsController::restore");
        $settings->post("/application/media/providers", "WhatsApp\\Controller\SettingsController::setContentProviderConfigs");
        $settings->get("/application/media/providers", "WhatsApp\\Controller\SettingsController::getContentProviderConfigs");
        $settings->delete("/application/media/providers/{name}", "WhatsApp\\Controller\SettingsController::deleteContentProviderConfigs");
        return $settings;
    }
} 