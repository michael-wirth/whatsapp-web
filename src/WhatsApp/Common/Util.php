<?php

namespace WhatsApp\Common;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Certificate\CertificateStorage;
use WhatsApp\Constants;

class Util
{
    public static function genResponse($meta, $payload, $errors, $app)
    {
        $response = array();
        if ($payload && $payload != new \stdClass) {
            $response = (array)$payload;
        }
        if ($meta) {
            Util::extractRequestId($meta, $app);
            $response['meta'] = $meta;
        } else {
            $response['meta'] = array("version" => getenv('WA_VERSION'), "api_status" => "stable");
        }
        $response = array_merge($response, $errors->get());
        $app["monolog"]->addInfo("Response: ", $response);
        return $response;
    }

    public static function extractRequestId($meta, $app)
    {
        if (isset($meta->request_id)) {
            if ($meta->request_id !== null && strlen($meta->request_id) > 0 && $meta->request_id !== $app[Constants::REQUEST_ID_FIELD]) {
                $app[Constants::INTERNAL_REQUEST_IDS_HTTP_HEADER] = $meta->request_id;
            }
            unset($meta->request_id);
        }
        if (isset($meta->internal_request_ids)) {
            if ($meta->internal_request_ids != null && strlen($meta->internal_request_ids) > 0) {
                if ($app[Constants::INTERNAL_REQUEST_IDS_HTTP_HEADER] === null) {
                    $app[Constants::INTERNAL_REQUEST_IDS_HTTP_HEADER] = $meta->internal_request_ids;
                } else {
                    $app[Constants::INTERNAL_REQUEST_IDS_HTTP_HEADER] .= "|" . $meta->internal_request_ids;
                }
            }
            unset($meta->internal_request_ids);
        }
    }

    public static function getPayload($response, &$meta, $errors, &$respCode)
    {
        if (isset($response->meta)) {
            $meta = $response->meta;
        }
        if (isset($response->response_code)) {
            $respCode = (int)$response->response_code;
        }
        if (isset($response->errors)) {
            foreach ($response->errors as $error) {
                if (isset($error->href)) {
                    $errors->add($error->code, $error->details, $error->href);
                } else {
                    $errors->add($error->code, $error->details);
                }
                $respCode = ApiError::getHttpStatus($error->code);
            }
            return null;
        }
        if (isset($response->error)) {
            $errors->add($response->error->errorcode, $response->error->errortext);
            $respCode = ApiError::getHttpStatus($response->error->errorcode);
            return null;
        }
        return $response->payload;
    }

    public static function getMandatoryParam($input, ApiErrors $errors, $param, array $allowed_values = null)
    {
        if (!self::hasParamInInput($input, $param)) {
            $errors->add(ApiError::PARAMETER_ABSENT, "Missing mandatory parameter '" . $param . "'");
            return null;
        }
        $value = self::getValueFromInput($input, $param);
        if ($allowed_values && !in_array($value, $allowed_values)) {
            $errors->add(ApiError::PARAMETER_INVALID, "Parameter '" . $param . "' has invalid value '" . $value . "'");
            $value = null;
        }
        return $value;
    }

    private static function hasParamInInput($input, $param)
    {
        $className = 'Symfony\Component\HttpFoundation\Request';
        if ($input instanceof $className) {
            return $input->request->has($param);
        } else if (is_array($input)) {
            return array_key_exists($param, $input);
        } else if (is_object($input)) {
            return isset($input->{$param});
        }
        return;
    }

    private static function getValueFromInput($input, $param)
    {
        $className = 'Symfony\Component\HttpFoundation\Request';
        if ($input instanceof $className) {
            return $input->request->get($param);
        } else if (is_array($input)) {
            if (array_key_exists($param, $input)) {
                return $input[$param];
            }
        } else if (is_object($input)) {
            return $input->$param;
        }
        return null;
    }

    public static function getOptionalParam($input, ApiErrors $errors, $param, $default = null, array $allowed_values = null)
    {
        if (!self::hasParamInInput($input, $param)) {
            return $default;
        }
        $value = self::getValueFromInput($input, $param);
        if ($allowed_values && !in_array($value, $allowed_values)) {
            $errors->add(ApiError::PARAMETER_INVALID, "Parameter '" . $param . "' has invalid value '" . $value . "'");
            $value = null;
        }
        return $value;
    }

    public static function getConditionalParam($input, ApiErrors $errors, $param, $dependent_name, $dependent_value, array $mandatory_for, array $not_required_for)
    {
        $className = 'Symfony\Component\HttpFoundation\Request';
        $value = self::getValueFromInput($input, $param);
        if ($dependent_value) {
            if ($value !== NULL && (is_string($value) ? strlen($value) !== 0 : $value)) {
                if (in_array($dependent_value, $not_required_for)) {
                    $errors->add(ApiError::PARAMETER_NOT_REQUIRED, "Parameter '" . $param . "' is not required for " . $dependent_name . " '" . $dependent_value . "'");
                }
            } else {
                if (in_array($dependent_value, $mandatory_for)) {
                    $errors->add(ApiError::PARAMETER_ABSENT, "Parameter '" . $param . "' is mandatory for " . $dependent_name . " '" . $dependent_value . "'");
                }
            }
        }
        return $value;
    }

    public static function getXorParamName($input, ApiErrors $errors, array $param_names)
    {
        if (empty($param_names)) {
            throw new \Exception("\$param_names is empty");
        }
        $filtered_param_names = array();
        foreach ($param_names as $name) {
            if (self::hasParamInInput($input, $name)) {
                $filtered_param_names[] = $name;
            }
        }
        if (empty($filtered_param_names)) {
            $errors->add(ApiError::PARAMETER_ABSENT, "Atleast one of the following parameters must be present: '" . implode("', '", $param_names) . "'");
            return;
        }
        if (count($filtered_param_names) > 1) {
            $errors->add(ApiError::PARAMETER_NOT_REQUIRED, "Exactly one of the following parameters must be present: '" . implode("', '", $param_names) . "'");
            return;
        }
        return $filtered_param_names[0];
    }

    public static function getOptionalParamBool(Request $request, ApiErrors $errors, $param, $default = null)
    {
        if (!$request->request->has($param)) {
            return $default;
        }
        $value = $request->request->get($param);
        if (!is_bool($value)) {
            $errors->add(ApiError::PARAMETER_INVALID, "Parameter '" . $param . "' has invalid value '" . $value . "'");
            $value = null;
        }
        return $value;
    }

    public static function isMultiConnect()
    {
        $val = getenv('WA_APP_MULTICONNECT') ?: "0";
        if ($val !== "0" && $val !== "1") {
            throw new \Exception("WA_APP_MULTICONNECT ({$val}) should be either 0 or 1");
        }
        return (int)$val;
    }

    public static function getShardId($key, $serverShards)
    {
        return crc32($key) % $serverShards;
    }

    public static function printArray($logger, $array)
    {
        foreach ($array as $key => $value) {
            $logger->debug("$key => $value");
        }
    }

    public static function printObject($logger, $name, $obj)
    {
        $logger->debug("Object: " . $name);
        foreach ($obj as $key => $value) {
            $logger->debug("$key => $value");
        }
    }

    public static function saveMedia($logger, $content, ApiErrors $errors)
    {
        $result = array();
        do {
            if (!is_writable(Constants::MEDIA_SHARED_DIR)) {
                $errors->add(ApiError::ACCESS_DENIED, "Media directory is not writable. Please check permissions");
                $respCode = Response::HTTP_FORBIDDEN;
                break;
            }
            if (!strlen($content)) {
                $errors->add(ApiError::PARAMETER_ABSENT, "Request had no (empty) content");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            if (strlen($content) > Constants::MAX_UPLOADED_MEDIA_SIZE_BYTES) {
                $errors->add(ApiError::PARAMETER_INVALID, "Request content exceeds size of 100 MB");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $id = Util::getUuid();
            $logger->debug("UUID: ", [$id]);
            $file = Util::getMediaPath($id);
            if (file_exists($file)) {
                $errors->add(ApiError::EXISTS, "Possible id conflict. Try again");
                $respCode = Response::HTTP_CONFLICT;
                break;
            }
            $fp = fopen($file, "w");
            fwrite($fp, $content);
            fclose($fp);
            $respCode = Response::HTTP_CREATED;
            $result["id"] = $id;
            $result["file"] = $file;
        } while (false);
        $result["respCode"] = $respCode;
        return $result;
    }

    public static function getUuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }

    public static function getMediaPath($mediaId)
    {
        return Constants::MEDIA_SHARED_DIR . $mediaId;
    }

    public static function saveCertificate($app, $content, $certType, ApiErrors $errors)
    {
        $result = array();
        do {
            $app['monolog']->debug("Certificate type: ", [$certType]);
            $respCode = Util::validateSaveCertificate($app, $content, $certType, $errors);
        } while (false);
        $result["respCode"] = $respCode;
        return $result;
    }

    public static function validateSaveCertificate($app, $content, $certType, ApiErrors $errors)
    {
        $CERT_MIN_PARTS = 3;
        do {
            if (!strlen($content)) {
                $errors->add(ApiError::PARAMETER_INVALID, "Request had no (empty) content");
                $app['monolog']->error("Request had no (empty) content");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $sections = explode(Constants::CERT_MARKER, $content);
            if (count($sections) < $CERT_MIN_PARTS) {
                $errors->add(ApiError::PARAMETER_INVALID, "Certificate must contain private key, cert and at least one " . "CA certificate sections");
                $app['monolog']->error("Certificate must contain private key, cert and at least one " . "CA certificate sections");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $cert = array();
            array_push($cert, $sections[0], $sections[1]);
            $certBlob = implode(Constants::CERT_MARKER, $cert);
            $caCert = array('');
            for ($idx = 2; $idx < count($sections); $idx++) {
                array_push($caCert, $sections[$idx]);
            }
            $caCertBlob = implode(Constants::CERT_MARKER, $caCert);
            $store = new CertificateStorage($app['db']);
            try {
                $store->updateCertificate($caCertBlob, $certBlob, Util::getWebServerName(), $certType);
            } catch (\Exception $e) {
                $app['monolog']->error("Failed to update certificate in db " . $e->getMessage());
                $errors->add(ApiError::GENERIC_ERROR, "Updating certificate in database failed");
                return Response::HTTP_INTERNAL_SERVER_ERROR;
            }
            $respCode = Response::HTTP_CREATED;
        } while (false);
        return $respCode;
    }

    public static function getWebServerName()
    {
        return getenv('WA_WEB_SERVERNAME') ?: 'localhost';
    }

    public static function base64UrlEncode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public static function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public static function getHostName()
    {
        return getenv('WEBAPP_HOSTNAME') ?: gethostname();
    }

    public static function validatePassword($password)
    {
        $validating_regex = sprintf(Constants::PASSWORD_COMPLEXITY_REGEX, preg_quote(Constants::PASSWORD_SPECIAL_CHARS, "/"), preg_quote(Constants::PASSWORD_SPECIAL_CHARS, "/"), Constants::MIN_PASSWORD_LENGTH, Constants::MAX_PASSWORD_LENGTH);
        return (preg_match($validating_regex, $password) !== 1) ? false : true;
    }

    public static function isSandboxMode()
    {
        $val = getenv('WA_MODE_SANDBOX') ?: "0";
        return $val === "1";
    }
}
