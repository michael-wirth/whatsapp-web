<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Connector;
use WhatsApp\Common\Util;

class CodeRequest
{
    public $cc;
    public $in;
    public $method;
    public $vname;
    public $vname_cert;
    public $twostep_code;
    public $reset;
}

class RegisterRequest
{
    public $code;
}

class SetShardsRequest
{
    public $cc;
    public $in;
    public $shards;
    public $twostep_code;
}

class AccountController
{
    const VALID_RESET_VALUES = array('wipe');

    public function codeRequest(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $codeRequest = $this->parseCodeRequest($request, $errors);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $response = Connector::send_receive($app, array('code_request' => $codeRequest), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $response = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($response, $respCode);
    }

    private function parseCodeRequest(Request $request, ApiErrors $errors)
    {
        $codeReq = new CodeRequest();
        do {
            $codeReq->cc = Util::getMandatoryParam($request, $errors, 'cc');
            $codeReq->in = Util::getMandatoryParam($request, $errors, 'phone_number');
            $codeReq->method = Util::getMandatoryParam($request, $errors, 'method');
            $param_name = Util::getXorParamName($request, $errors, ['cert', 'vname_cert']);
            if (!is_null($param_name)) {
                $codeReq->$param_name = Util::getMandatoryParam($request, $errors, $param_name);
                $codeReq->vname = $codeReq->vname_cert;
            }
            $codeReq->reset = Util::getOptionalParam($request, $errors, 'reset', null, self::VALID_RESET_VALUES);
            $codeReq->twostep_code = Util::getConditionalParam($request, $errors, 'pin', 'reset', $codeReq->reset, array(), self::VALID_RESET_VALUES);
        } while (false);
        return $codeReq;
    }

    public function register(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $registerRequest = $this->parseRegisterRequest($request, $errors);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $response = Connector::send_receive($app, array('register' => $registerRequest), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $response = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($response, $respCode);
    }

    private function parseRegisterRequest(Request $request, ApiErrors $errors)
    {
        $regReq = new RegisterRequest();
        do {
            $regReq->code = Util::getMandatoryParam($request, $errors, 'code');
        } while (false);
        return $regReq;
    }

    public function setShards(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $codeRequest = $this->parseSetShards($request, $errors);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $response = Connector::send_receive($app, array('set_shards' => $codeRequest), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $response = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($response, $respCode);
    }

    private function parseSetShards(Request $request, ApiErrors $errors)
    {
        $setShardsReq = new SetShardsRequest();
        do {
            $setShardsReq->cc = Util::getMandatoryParam($request, $errors, 'cc');
            $setShardsReq->in = Util::getMandatoryParam($request, $errors, 'phone_number');
            $setShardsReq->shards = Util::getMandatoryParam($request, $errors, 'shards');
            $setShardsReq->twostep_code = Util::getOptionalParam($request, $errors, 'pin');
        } while (false);
        return $setShardsReq;
    }

    public function getShards(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('get_shards' => Null), 'control', $errors, $app['monolog']);
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