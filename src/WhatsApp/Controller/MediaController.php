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

class MediaController
{
    public function upload(Request $request, Application $app)
    {
        $meta = null;
        $respCode = Response::HTTP_OK;
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $mimeType = $request->headers->get('Content-Type');
        if (empty($mimeType)) {
            $respCode = Response::HTTP_BAD_REQUEST;
            $errors->add(ApiError::PARAMETER_ABSENT, "Content-Type missing");
            $post = Util::genResponse($meta, null, $errors, $app);
            return $app->json($post, $respCode);
        }
        $result = Util::saveMedia($app['monolog'], $request->getContent(), $errors);
        $respCode = $result ["respCode"];
        if ($respCode == Response::HTTP_CREATED) {
            do {
                $response = Connector::send_receive($app, array('upload_media' => array('filename' => $result["id"], 'mimetype' => $mimeType)), 'control', $errors, $app['monolog']);
                if ($errors->hasError()) {
                    $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                    break;
                }
                $payload = Util::getPayload($response, $meta, $errors, $respCode);
            } while (false);
        }
        if (array_key_exists("id", $result)) {
            $file = Util::getMediaPath($result["id"]);
            if (file_exists($file)) {
                $app['monolog']->warning("Upload failed, deleting temporary media file: ", [$file]);
                unlink($file);
            }
        }
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function remove(Request $request, Application $app, $id)
    {
        $meta = null;
        $respCode = Response::HTTP_OK;
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $app['monolog']->debug("Media id: ", [$id]);
        do {
            $response = Connector::send_receive($app, array('delete_media' => $id), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function download(Request $request, Application $app, $id)
    {
        $app['monolog']->debug("Media id: ", [$id]);
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array('download_media' => $id), 'control', $errors, $app['monolog']);
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
            $file = $payloadArray["link"];
            if (!file_exists($file)) {
                $app['monolog']->warning("Failed to find shared media file at path " . $file);
                $errors->add(ApiError::NOT_FOUND, "File with id " . $id . " not found");
                $respCode = Response::HTTP_NOT_FOUND;
                $post = Util::genResponse($meta, null, $errors, $app);
                return $app->json($post, $respCode);
            }
            $headers = array();
            if (array_key_exists('mimeType', $payloadArray)) {
                $headers['Content-Type'] = $payloadArray['mimeType'];
            }
            $response = new BinaryFileResponse($file, 200, $headers);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
            return $response;
        }
    }
} 