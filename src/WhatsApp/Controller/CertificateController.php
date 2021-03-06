<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\Constraints;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Connector;
use WhatsApp\Common\Util;
use WhatsApp\Constants;

class CertificateController
{
    public function upload(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $app['monolog']->debug("Content-type: ", [$request->headers->get('Content-Type')]);
        $result = Util::saveCertificate($app, $request->getContent(), Constants::CERT_TYPE_EXTERNAL, $errors);
        $respCode = $result ["respCode"];
        $post = Util::genResponse(null, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function downloadCA(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $file = Constants::CERT_DIR_INSTALLED . Constants::CERT_CA_FILENAME;
        if (!file_exists($file)) {
            $errors->add(ApiError::NOT_FOUND, "External CA cert file not found");
            $respCode = Response::HTTP_NOT_FOUND;
            $post = Util::genResponse(null, null, $errors, $app);
            return $app->json($post, $respCode);
        }
        if (Util::isSandboxMode()) {
            $errors->add(ApiError::ACCESS_DENIED, "Certain operations are not allowed in sandbox mode");
            $respCode = Response::HTTP_FORBIDDEN;
            $post = Util::genResponse(null, null, $errors, $app);
            return $app->json($post, $respCode);
        }
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        $respCode = Response::HTTP_OK;
        return $response;
    }

    public function setWebhookCaCerts(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            if ($request->headers->get('Content-Length') == 0) {
                $errors->add(ApiError::PARAMETER_ABSENT, "No cert data found");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            if ($request->headers->get('Content-Type') != "text/plain") {
                $errors->add(ApiError::PARAMETER_INVALID, "Content-Type must be text/plain");
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $certContent = $request->getContent();
            $response = Connector::send_receive($app, array('set_webhook_ca_certs' => $certContent), 'control', $errors);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function getWebhookCaCerts(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('get_webhook_ca_certs' => Null), 'control', $errors);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        if ($errors->hasError()) {
            $post = Util::genResponse($meta, null, $errors, $app);
            return $app->json($post, $respCode);
        } else {
            $payloadArray = (array)$payload;
            $response = new Response($payloadArray["Content-Raw"]);
            $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'webhookCaCerts.pem');
            $response->headers->set('Content-Disposition', $disposition);
            return $response;
        }
    }

    public function deleteWebhookCaCerts(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $certContent = $request->getContent();
            $response = Connector::send_receive($app, array('delete_webhook_ca_certs' => $certContent), 'control', $errors);
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