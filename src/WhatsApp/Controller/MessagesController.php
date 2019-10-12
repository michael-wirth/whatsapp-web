<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Connector;
use WhatsApp\Common\Util;
use WhatsApp\Common\Whitelist;

class Message
{
    public $is_preview_url;
    public $recipient_type;
    public $render_mentions;
    public $to;
    public $type;
    public $ttl;
}

class MessagesController
{
    const ALLOWED_RECIPIENT_TYPES = array('group', 'individual');
    const ALLOWED_TYPES = array('audio', 'document', 'hsm', 'image', 'location', 'text', 'video', 'contacts', 'template');
    const BODY_MANDATORY_FOR_TYPES = array('text');
    const BODY_NOT_REQUIRED_FOR_TYPES = array('audio', 'document', 'hsm', 'image', 'location', 'video', 'contacts');
    const FILENAME_MANDATORY_FOR_TYPES = array();
    const FILENAME_NOT_REQUIRED_FOR_TYPES = array('audio', 'hsm', 'image', 'location', 'text', 'video', 'contacts', 'template');
    const CAPTION_MANDATORY_FOR_TYPES = array();
    const CAPTION_NOT_REQUIRED_FOR_TYPES = array('audio', 'hsm', 'location', 'text', 'contacts');
    const MEDIA_ID_MANDATORY_FOR_TYPES = array();
    const MEDIA_ID_NOT_REQUIRED_FOR_TYPES = array('hsm', 'location', 'text', 'contacts');
    const NAMESPACE_MANDATORY_FOR_TYPES = array('hsm', 'template');
    const NAMESPACE_NOT_REQUIRED_FOR_TYPES = array('audio', 'document', 'image', 'location', 'text', 'video');
    const ELEMENT_NAME_MANDATORY_FOR_TYPES = array('hsm');
    const ELEMENT_NAME_NOT_REQUIRED_FOR_TYPES = array('audio', 'document', 'image', 'location', 'text', 'video', 'contacts', 'template');
    const LANGUAGE_OPTIONAL_FOR_TYPES = array('hsm', 'template');
    const LANGUAGE_POLICIES = array('fallback', 'deterministic');
    private $logger;

    public function send(Request $request, Application $app)
    {
        $this->logger = $app['monolog'];
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_CREATED;
        do {
            $message = $this->parseSendRequest($request, $errors);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            if (!Whitelist::recipient_ok($app, $message->to, ($message->recipient_type == 'group'))) {
                $errors->add(ApiError::BLOCKED_RECIPIENT, "Recipient '" . $message->to . "' is not whitelisted");
                $respCode = Response::HTTP_FORBIDDEN;
                break;
            }
            $response = Connector::send_receive($app, $message, 'message', $errors, $this->logger);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    private function parseSendRequest(Request $request, ApiErrors $errors)
    {
        $message = new Message();
        do {
            $message->to = Util::getMandatoryParam($request, $errors, 'to');
            $message->type = Util::getOptionalParam($request, $errors, 'type', 'text', self::ALLOWED_TYPES);
            $message->recipient_type = Util::getOptionalParam($request, $errors, 'recipient_type', 'individual', self::ALLOWED_RECIPIENT_TYPES);
            $message->is_preview_url = Util::getOptionalParamBool($request, $errors, 'preview_url', false);
            $message->render_mentions = Util::getOptionalParamBool($request, $errors, 'render_mentions', false);
            $message->ttl = Util::getOptionalParam($request, $errors, 'ttl');
            if (!is_null($message->ttl) && empty($message->ttl)) {
                $errors->add(ApiError::PARAMETER_INVALID, "Empty TTL value not allowed");
                break;
            }
            $typeArray = array($message->type);
            $notReqdTypesArray = array_diff(self::ALLOWED_TYPES, array($message->type));
            $type = Util::getConditionalParam($request, $errors, $message->type, 'type', $message->type, $typeArray, $notReqdTypesArray);
            foreach ($notReqdTypesArray as $typeName) {
                Util::getConditionalParam($request, $errors, $typeName, 'type', $message->type, array(), $typeArray);
            }
            $typeName = $message->type;
            if ($typeName) {
                $message->$typeName = $type;
                $this->validateMessageTypeObject($typeName, $type, $errors);
            }
        } while (false);
        return $message;
    }

    private function validateMessageTypeObject($typeName, $type, ApiErrors $errors)
    {
        Util::getConditionalParam($type, $errors, 'body', 'type', $typeName, self::BODY_MANDATORY_FOR_TYPES, self::BODY_NOT_REQUIRED_FOR_TYPES);
        Util::getConditionalParam($type, $errors, 'id', 'type', $typeName, self::MEDIA_ID_MANDATORY_FOR_TYPES, self::MEDIA_ID_NOT_REQUIRED_FOR_TYPES);
        Util::getConditionalParam($type, $errors, 'namespace', 'type', $typeName, self::NAMESPACE_MANDATORY_FOR_TYPES, self::NAMESPACE_NOT_REQUIRED_FOR_TYPES);
        Util::getConditionalParam($type, $errors, 'element_name', 'type', $typeName, self::ELEMENT_NAME_MANDATORY_FOR_TYPES, self::ELEMENT_NAME_NOT_REQUIRED_FOR_TYPES);
        Util::getConditionalParam($type, $errors, 'caption', 'type', $typeName, self::CAPTION_MANDATORY_FOR_TYPES, self::CAPTION_NOT_REQUIRED_FOR_TYPES);
        Util::getConditionalParam($type, $errors, 'filename', 'type', $typeName, self::FILENAME_MANDATORY_FOR_TYPES, self::FILENAME_NOT_REQUIRED_FOR_TYPES);
        $this->validateNestedLanguageObject($type, $errors);
    }

    private function validateNestedLanguageObject($type, ApiErrors $errors)
    {
        $language = Util::getOptionalParam($type, $errors, 'language');
        if ($language !== null) {
            Util::getXorParamName($type, $errors, array('language', 'fallback_lg'));
            Util::getXorParamName($type, $errors, array('language', 'fallback_lc'));
            Util::getMandatoryParam($language, $errors, 'code');
            Util::getMandatoryParam($language, $errors, 'policy', self::LANGUAGE_POLICIES);
        }
    }

    public function delete(Request $request, Application $app, $id)
    {
        $app['monolog']->debug("Message id: ", [$id]);
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            break;
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function get(Request $request, Application $app, $id)
    {
        $app['monolog']->debug("Message id: ", [$id]);
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            break;
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function updateStatus(Request $request, Application $app, $id)
    {
        $app['monolog']->debug("Message id: ", [$id]);
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $message = array('mark_messages_read' => array('message_id' => $id));
            $response = Connector::send_receive($app, $message, 'message', $errors, $this->logger);
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