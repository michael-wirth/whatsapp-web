<?php

namespace WhatsApp\Common;

use Symfony\Component\HttpFoundation\Response;

class ApiError
{
    const UNDEFINED_ERROR = 0;
    const GENERIC_ERROR = 1000;
    const MESSAGE_TOO_LONG = 1001;
    const INVALID_RECIPIENT_TYPE = 1002;
    const NOT_GROUP_PARTICIPANT = 1003;
    const EXISTS = 1004;
    const ACCESS_DENIED = 1005;
    const NOT_FOUND = 1006;
    const BLOCKED_RECIPIENT = 1007;
    const PARAMETER_ABSENT = 1008;
    const PARAMETER_INVALID = 1009;
    const PARAMETER_NOT_REQUIRED = 1010;
    const SERVICE_NOT_READY = 1011;
    const UNKNOWN_GROUP = 1012;
    const INVALID_USER = 1013;
    const INTERNAL_ERROR = 1014;
    const TOO_MANY_REQUESTS = 1015;
    const SYSTEM_OVERLOAD = 1016;
    const NOT_PRIMARY_MASTER = 1017;
    const NOT_PRIMARY_COREAPP = 1018;
    const NOT_GROUP_ADMIN = 1019;
    const BAD_GROUP = 1020;
    const BAD_USER = 1021;
    const WEBHOOKS_URL_NOT_CONFIGURED = 1022;
    const DATABASE_ERROR = 1023;
    const PASSWORD_CHANGE_REQUIRED = 1024;
    const INVALID_REQUEST = 1025;
    const RECEIVER_INCAPABLE = 1026;
    const HSM_PARAM_COUNT_MISMATCH = 2000;
    const HSM_ELEMENT_MISSING = 2001;
    const HSM_LANGUAGEPACK_FETCH_FAILED = 2002;
    const HSM_LANGUAGEPACK_MISSING = 2003;
    const HSM_PARAM_LENGTH_TOO_LONG = 2004;
    const HSM_HYDRATED_TEXT_TOO_LONG = 2005;
    const HSM_WHITESPACE_POLICY_VIOLATED = 2006;
    const HSM_FORMAT_CHARACTER_POLICY_VIOLATED = 2007;
    const HSM_MEDIA_FORMAT_UNSUPPORTED = 2008;
    const HSM_REQUIRED_COMPONENT_MISSING = 2009;
    const HSM_INVALID_HYDRATED_URL = 2010;
    const HSM_INVALID_PHONE_NUMBER = 2011;
    const HSM_PARAMETER_FORMAT_MISMATCH = 2012;
    const HSM_BUTTONS_UNSUPPORTED = 2013;
    const codemap = array(self::UNDEFINED_ERROR => array("title" => "undefined", "http_status" => Response::HTTP_INTERNAL_SERVER_ERROR), self::GENERIC_ERROR => array("title" => "Generic error", "http_status" => Response::HTTP_INTERNAL_SERVER_ERROR), self::MESSAGE_TOO_LONG => array("title" => "Message too long", "http_status" => Response::HTTP_BAD_REQUEST), self::INVALID_RECIPIENT_TYPE => array("title" => "Invalid recipient type", "http_status" => Response::HTTP_BAD_REQUEST), self::NOT_GROUP_PARTICIPANT => array("title" => "Not a group participant", "http_status" => Response::HTTP_BAD_REQUEST), self::EXISTS => array("title" => "Resource already exists", "http_status" => Response::HTTP_CONFLICT), self::ACCESS_DENIED => array("title" => "Access denied", "http_status" => Response::HTTP_FORBIDDEN), self::NOT_FOUND => array("title" => "Resource not found", "http_status" => Response::HTTP_NOT_FOUND), self::BLOCKED_RECIPIENT => array("title" => "Recipient blocked to receive message", "http_status" => Response::HTTP_FORBIDDEN), self::PARAMETER_ABSENT => array("title" => "Required parameter is missing", "http_status" => Response::HTTP_BAD_REQUEST), self::PARAMETER_INVALID => array("title" => "Parameter value is not valid", "http_status" => Response::HTTP_BAD_REQUEST), self::PARAMETER_NOT_REQUIRED => array("title" => "Parameter is not required", "http_status" => Response::HTTP_BAD_REQUEST), self::SERVICE_NOT_READY => array("title" => "Service not ready", "http_status" => Response::HTTP_INTERNAL_SERVER_ERROR), self::UNKNOWN_GROUP => array("title" => "Group is unknown", "http_status" => Response::HTTP_NOT_FOUND), self::INVALID_USER => array("title" => "User is not valid", "http_status" => Response::HTTP_BAD_REQUEST), self::INTERNAL_ERROR => array("title" => "Internal error", "http_status" => Response::HTTP_INTERNAL_SERVER_ERROR), self::TOO_MANY_REQUESTS => array("title" => "Too many requests", "http_status" => Response::HTTP_TOO_MANY_REQUESTS), self::SYSTEM_OVERLOAD => array("title" => "System overloaded", "http_status" => Response::HTTP_SERVICE_UNAVAILABLE), self::NOT_PRIMARY_MASTER => array("title" => "Not a primary master", "http_status" => Response::HTTP_INTERNAL_SERVER_ERROR), self::NOT_PRIMARY_COREAPP => array("title" => "Not a primary coreapp", "http_status" => Response::HTTP_INTERNAL_SERVER_ERROR), self::NOT_GROUP_ADMIN => array("title" => "Not a group admin", "http_status" => Response::HTTP_BAD_REQUEST), self::BAD_GROUP => array("title" => "Bad group", "http_status" => Response::HTTP_BAD_REQUEST), self::BAD_USER => array("title" => "Bad user", "http_status" => Response::HTTP_BAD_REQUEST), self::WEBHOOKS_URL_NOT_CONFIGURED => array("title" => "Webhooks URL is not configured", "http_status" => Response::HTTP_PRECONDITION_FAILED), self::DATABASE_ERROR => array("title" => "Database error occurred", "http_status" => Response::HTTP_INTERNAL_SERVER_ERROR), self::PASSWORD_CHANGE_REQUIRED => array("title" => "Password change required", "http_status" => Response::HTTP_UNAUTHORIZED), self::INVALID_REQUEST => array("title" => "Request is not valid", "http_status" => Response::HTTP_BAD_REQUEST), self::RECEIVER_INCAPABLE => array("title" => "Receiver is incapable of receiving this message", "http_status" => Response::HTTP_NOT_IMPLEMENTED), self::HSM_PARAM_COUNT_MISMATCH => array("title" => "Number of parameters does not match the expected number of params", "http_status" => Response::HTTP_BAD_REQUEST), self::HSM_ELEMENT_MISSING => array("title" => "Template name does not exist in the translation", "http_status" => Response::HTTP_NOT_FOUND), self::HSM_LANGUAGEPACK_FETCH_FAILED => array("title" => "Failed to find the translation for the requested language and locale", "http_status" => Response::HTTP_NOT_FOUND), self::HSM_LANGUAGEPACK_MISSING => array("title" => "Could not find translation for the requested language and locale", "http_status" => Response::HTTP_NOT_FOUND), self::HSM_PARAM_LENGTH_TOO_LONG => array("title" => "Parameter length too long", "http_status" => Response::HTTP_BAD_REQUEST), self::HSM_HYDRATED_TEXT_TOO_LONG => array("title" => "Translated text too long", "http_status" => Response::HTTP_BAD_REQUEST), self::HSM_WHITESPACE_POLICY_VIOLATED => array("title" => "Whitespace policy violated", "http_status" => Response::HTTP_BAD_REQUEST), self::HSM_FORMAT_CHARACTER_POLICY_VIOLATED => array("title" => "Format character policy violated", "http_status" => Response::HTTP_BAD_REQUEST), self::HSM_MEDIA_FORMAT_UNSUPPORTED => array("title" => "Media format used is unsupported", "http_status" => Response::HTTP_BAD_REQUEST), self::HSM_REQUIRED_COMPONENT_MISSING => array("title" => "Required component in the template is missing", "http_status" => Response::HTTP_BAD_REQUEST), self::HSM_INVALID_HYDRATED_URL => array("title" => "URL in button component is invalid", "http_status" => Response::HTTP_BAD_REQUEST), self::HSM_INVALID_PHONE_NUMBER => array("title" => "Phone Number in button component is invalid", "http_status" => Response::HTTP_BAD_REQUEST), self::HSM_PARAMETER_FORMAT_MISMATCH => array("title" => "Parameter format does not match format in the created template", "http_status" => Response::HTTP_BAD_REQUEST), self::HSM_BUTTONS_UNSUPPORTED => array("title" => "Buttons are unsupported by the receiver", "http_status" => Response::HTTP_NOT_IMPLEMENTED),);
    private $code;
    private $details;
    private $href;

    public function __construct($code, $details = "", $href = "")
    {
        if (!isset(self::codemap[$code]["title"])) {
            $details = "Undefined error code " . $code;
            $code = self::UNDEFINED_ERROR;
        }
        $this->code = $code;
        $this->details = $details;
        $this->href = $href;
    }

    public static function getHttpStatus($code)
    {
        if (array_key_exists($code, self::codemap)) {
            return self::codemap[$code]["http_status"];
        }
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function get()
    {
        $response = array('code' => $this->code, 'title' => self::codemap[$this->code]["title"]);
        if (strlen($this->details)) {
            $response['details'] = $this->details;
        }
        if (strlen($this->href)) {
            $response['href'] = $this->href;
        }
        return $response;
    }
}