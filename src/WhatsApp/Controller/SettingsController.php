<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Connector;
use WhatsApp\Common\Util;
use WhatsApp\Constants;

class BusinessProfile
{
    public $address;
    public $description;
    public $vertical;
    public $email;
    public $websites;
}

class AppSettings
{
    public $webhooks;
    public $webcallbacks;
    public $on_call_pager;
    public $pass_through;
    public $callback_persist;
    public $sent_status;
    public $callback_backoff_delay_ms;
    public $max_callback_backoff_delay_ms;
    public $contacts_api_rate_limit;
    public $control_api_rate_limit;
    public $healthcheck_api_rate_limit;
    public $messaging_api_rate_limit;
    public $contacts_scrape_rate_limit;
    public $allow_nonexistent_users;
    public $heartbeat_interval;
    public $unhealthy_interval;
    public $media;
    public $dogfood_user_limit;
}

class ContentProviderConfig
{
    public $name;
    public $type;
    public $config;
}

class SettingsController
{
    const ABOUT_KEY_APCU = 'about-status-key';
    const ABOUT_KEY_CACHE_TTL = 30;

    public function getAppSettings(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('get_settings' => Null), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function setAppSettings(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $appSettings = $this->parseAppSettingRequest($request, $errors);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $response = Connector::send_receive($app, array('set_settings' => $appSettings), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    private function parseAppSettingRequest(Request $request, ApiErrors $errors)
    {
        $appSettings = new AppSettings();
        $appSettings->webcallbacks = Util::getOptionalParam($request, $errors, 'webcallbacks');
        $appSettings->on_call_pager = Util::getOptionalParam($request, $errors, 'on_call_pager');
        $appSettings->pass_through = Util::getOptionalParamBool($request, $errors, 'pass_through');
        $appSettings->callback_persist = Util::getOptionalParamBool($request, $errors, 'callback_persist');
        $appSettings->sent_status = Util::getOptionalParamBool($request, $errors, 'sent_status');
        $appSettings->callback_backoff_delay_ms = Util::getOptionalParam($request, $errors, 'callback_backoff_delay_ms');
        $appSettings->max_callback_backoff_delay_ms = Util::getOptionalParam($request, $errors, 'max_callback_backoff_delay_ms');
        $appSettings->contacts_api_rate_limit = Util::getOptionalParam($request, $errors, 'contacts_api_rate_limit');
        $appSettings->control_api_rate_limit = Util::getOptionalParam($request, $errors, 'control_api_rate_limit');
        $appSettings->healthcheck_api_rate_limit = Util::getOptionalParam($request, $errors, 'healthcheck_api_rate_limit');
        $appSettings->messaging_api_rate_limit = Util::getOptionalParam($request, $errors, 'messaging_api_rate_limit');
        $appSettings->contacts_scrape_rate_limit = Util::getOptionalParam($request, $errors, 'contacts_scrape_rate_limit');
        $appSettings->unique_message_sends_rate_limit = Util::getOptionalParam($request, $errors, 'unique_message_sends_rate_limit');
        $appSettings->allow_nonexistent_users = Util::getOptionalParamBool($request, $errors, 'allow_nonexistent_users');
        $appSettings->heartbeat_interval = Util::getOptionalParam($request, $errors, 'heartbeat_interval');
        $appSettings->unhealthy_interval = Util::getOptionalParam($request, $errors, 'unhealthy_interval');
        $appSettings->webhooks = Util::getOptionalParam($request, $errors, 'webhooks');
        $appSettings->media = Util::getOptionalParam($request, $errors, 'media');
        $appSettings->dogfood_user_limit = Util::getOptionalParam($request, $errors, 'dogfood_user_limit');
        $appSettings->template_send_side_validation_disabled = Util::getOptionalParamBool($request, $errors, 'template_send_side_validation_disabled');
        $appSettings->axolotl_context_striping_disabled = Util::getOptionalParamBool($request, $errors, 'axolotl_context_striping_disabled');
        $appSettings->axolotl_context_striping_logical_core_multiplier = Util::getOptionalParam($request, $errors, 'axolotl_context_striping_logical_core_multiplier');
        if (count(array_filter(get_object_vars($appSettings), function ($value) {
                return isset($value);
            })) == 0) {
            $errors->add(ApiError::PARAMETER_INVALID, "No valid parameters provided");
        }
        return $appSettings;
    }

    public function deleteAppSettings(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('delete_settings' => new AppSettings()), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function getBusinessProfile(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('get_business_profile' => Null), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function setBusinessProfile(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $businessProfile = $this->parseBusinessProfileRequest($request, $errors);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $response = Connector::send_receive($app, array('set_business_profile' => $businessProfile), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    private function parseBusinessProfileRequest(Request $request, ApiErrors $errors)
    {
        $businessProfile = new BusinessProfile();
        $businessProfile->address = Util::getOptionalParam($request, $errors, 'address');
        $businessProfile->description = Util::getOptionalParam($request, $errors, 'description');
        $businessProfile->vertical = Util::getOptionalParam($request, $errors, 'vertical');
        $businessProfile->email = Util::getOptionalParam($request, $errors, 'email');
        $businessProfile->websites = Util::getOptionalParam($request, $errors, 'websites');
        if (count($businessProfile->websites) > 2) {
            $errors->add(ApiError::PARAMETER_INVALID, "Max number of websites allowed is 2");
        }
        return $businessProfile;
    }

    public function getProfilePhoto(Request $request, Application $app)
    {
        $format = $request->query->get('format');
        if ($format == 'link') {
            return $this->getProfilePhotoUrl($app);
        } else {
            return $this->getProfilePhotoData($app);
        }
    }

    public function getProfilePhotoUrl(Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('get_profile_photo_url' => Null), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    private function getProfilePhotoData(Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('get_profile_photo' => Null), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $payloadArray = (array)$payload;
        if ($errors->hasError()) {
            $post = Util::genResponse($meta, null, $errors, $app);
            return $app->json($post, $respCode);
        } else {
            $response = new Response(base64_decode($payloadArray["Content-Raw"]));
            $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'profilePicture.jpg');
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-Type', 'image/jpeg');
            return $response;
        }
    }

    public function setProfilePhoto(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_CREATED;
        do {
            $headerContentLength = $request->headers->get('Content-Length');
            $actualContent = $request->getContent();
            $actualContentLength = strlen($actualContent);
            $app['monolog']->debug(memory_get_usage() . "\n");
            if (($headerContentLength != null && $headerContentLength >= Constants::MAX_PROFILE_PHOTO_SIZE_BYTES) || $actualContentLength >= Constants::MAX_PROFILE_PHOTO_SIZE_BYTES) {
                $errors->add(ApiError::PARAMETER_INVALID, "Photo size too large. Max allowed size: " . Constants::MAX_PROFILE_PHOTO_SIZE_BYTES . " bytes");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            if ($actualContentLength == 0) {
                $errors->add(ApiError::PARAMETER_ABSENT, "No data found");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $actualContent = base64_encode($actualContent);
            $response = Connector::send_receive($app, array('set_profile_photo_full' => $actualContent), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function deleteProfilePhoto(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array("delete_profile_photo" => Null), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function getAbout(Request $request, Application $app)
    {
        $fetchSuccess = false;
        $jsonResponse = apcu_fetch(self::ABOUT_KEY_APCU, $fetchSuccess);
        if ($fetchSuccess === true) {
            $app['monolog']->info("Serving about status from cache");
            return $jsonResponse;
        }
        $app['monolog']->info("Unable to fetch status from cache. Falling back to server API.");
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('get_about' => Null), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        $jsonResponse = $app->json($post, $respCode);
        if ($respCode === Response::HTTP_OK) {
            $stored = apcu_store(self::ABOUT_KEY_APCU, $jsonResponse, self::ABOUT_KEY_CACHE_TTL);
            if ($stored === false) {
                $app['monolog']->error("Unable to store about status to cache");
            } else {
                $app['monolog']->info("About Status stored to cache");
            }
        }
        return $jsonResponse;
    }

    public function setAbout(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $text = Util::getMandatoryParam($request, $errors, 'text');
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            if (apcu_exists(self::ABOUT_KEY_APCU)) {
                $deleted = apcu_delete(self::ABOUT_KEY_APCU);
                if ($deleted === false) {
                    $app['monolog']->error("Unable to reset about status in cache");
                    $errors->add(ApiError::INTERNAL_ERROR, "Unable to save status. Please try again.");
                    break;
                } else {
                    $app['monolog']->info("About status resetted in cache");
                }
            }
            $response = Connector::send_receive($app, array('set_about' => $text), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function setTwoFac(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $twoFacCode = Util::getMandatoryParam($request, $errors, 'pin');
            if (Util::isSandboxMode()) {
                $errors->add(ApiError::ACCESS_DENIED, "Certain operations are not allowed in sandbox mode");
                $respCode = Response::HTTP_FORBIDDEN;
                break;
            }
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            if (!$twoFacCode) {
                $errors->add(ApiError::PARAMETER_INVALID, "Pin must not be empty");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $response = Connector::send_receive($app, array('set_twostep' => $twoFacCode), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function unsetTwoFac(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            if (Util::isSandboxMode()) {
                $errors->add(ApiError::ACCESS_DENIED, "Certain operations are not allowed in sandbox mode");
                $respCode = Response::HTTP_FORBIDDEN;
                break;
            }
            $response = Connector::send_receive($app, array('unset_twostep' => Null), 'control', $errors, $app['monolog']);
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function deleteMessages(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $param_name = Util::getXorParamName($request, $errors, ['age', 'before']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $param_value = Util::getMandatoryParam($request, $errors, $param_name);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $response = Connector::send_receive($app, array('delete_messages' => array($param_name => $param_value)), 'control', $errors);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function backup(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $password = Util::getMandatoryParam($request, $errors, 'password');
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            if (Util::isSandboxMode()) {
                $errors->add(ApiError::ACCESS_DENIED, "Certain operations are not allowed in sandbox mode");
                $respCode = Response::HTTP_FORBIDDEN;
                break;
            }
            $response = Connector::send_receive($app, array('export' => array('password' => $password)), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function restore(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $password = Util::getMandatoryParam($request, $errors, 'password');
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $data = Util::getMandatoryParam($request, $errors, 'data');
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            if (Util::isSandboxMode()) {
                $errors->add(ApiError::ACCESS_DENIED, "Certain operations are not allowed in sandbox mode");
                $respCode = Response::HTTP_FORBIDDEN;
                break;
            }
            $importRequestBody = array('password' => $password, 'data' => $data);
            $response = Connector::send_receive($app, array('import' => $importRequestBody), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function setContentProviderConfigs(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $content = json_decode($request->getContent(), true);
            if (count($content) != 1) {
                $errors->add(ApiError::PARAMETER_INVALID, "Can only add 1 config at a time");
            }
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $contentProviderConfig = new ContentProviderConfig();
            $allowedProviderTypes = array("www", "everstore");
            $config = $content[0];
            $contentProviderConfig->name = Util::getMandatoryParam($config, $errors, 'name');
            $contentProviderConfig->type = Util::getMandatoryParam($config, $errors, 'type', $allowedProviderTypes);
            $contentProviderConfig->config = Util::getMandatoryParam($config, $errors, 'config');
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $response = Connector::send_receive($app, array('set_content_provider_configs' => $content), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function getContentProviderConfigs(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('get_content_provider_configs' => Null), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function deleteContentProviderConfigs(Request $request, Application $app, $name)
    {
        $app['monolog']->debug("Configuration name to delete: ", [$name]);
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('delete_content_provider_configs' => $name), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }
} 