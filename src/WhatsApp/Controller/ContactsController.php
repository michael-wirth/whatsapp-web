<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Connector;
use WhatsApp\Common\Util;
use WhatsApp\Constants;

class ContactRequest
{
    public $blocking;
    public $contacts;
}

class ContactsController
{
    private $logger;

    public function checkContacts(Request $request, Application $app)
    {
        $this->logger = $app["monolog"];
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $contactRequest = $this->parseContactRequest($request, $errors);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $response = Connector::send_receive($app, $contactRequest, "contact", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    private function parseContactRequest(Request $request, ApiErrors $errors)
    {
        $contactRequest = new ContactRequest();
        $contactRequest->blocking = Util::getOptionalParam($request, $errors, "blocking", "no_wait", array("wait", "no_wait"));
        $contactRequest->contacts = Util::getMandatoryParam($request, $errors, "contacts");
        if (!is_array($contactRequest->contacts)) {
            $errors->add(ApiError::PARAMETER_INVALID, "Contacts field has to be an array");
        }
        if (Util::isSandboxMode() && count($contactRequest->contacts) > Constants::SB_MAX_CHECK_CONTACTS) {
            $errors->add(ApiError::PARAMETER_INVALID, "Sandbox mode: Exceeded maximum allowed contacts in Check contacts API");
        }
        if (!count($contactRequest->contacts)) {
            $errors->add(ApiError::PARAMETER_INVALID, "Contacts field must have atleast one entry");
        }
        return $contactRequest;
    }
} 