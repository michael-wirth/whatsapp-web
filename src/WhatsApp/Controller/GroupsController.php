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

class GroupsController
{
    public function createGroup(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $subject = Util::getMandatoryParam($request, $errors, "subject");
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $participants = Util::getOptionalParam($request, $errors, "participants");
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            if (Util::isSandboxMode()) {
                $response = Connector::send_receive($app, array("get_all_groups" => Null), "control", $errors, $app['monolog']);
                if ($errors->hasError()) {
                    $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                    break;
                }
                if (count($response->payload->groups) >= Constants::SB_MAX_GROUP_MEMBERSHIP) {
                    $message = "Sandbox mode: Exceeded maximum allowed group memberships";
                    $app['monolog']->info($message);
                    $errors->add(ApiError::ACCESS_DENIED, $message);
                    $respCode = Response::HTTP_FORBIDDEN;
                    break;
                }
            }
            $groupInfo["subject"] = $subject;
            $groupInfo["participants"] = $participants;
            $response = Connector::send_receive($app, array("create_group" => $groupInfo), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function updateGroup(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $subject = Util::getMandatoryParam($request, $errors, "subject");
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $groupInfo["subject"] = $subject;
            $groupInfo["group"] = $id;
            $response = Connector::send_receive($app, array("set_group_subject" => $groupInfo), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function getAllGroups(Request $request, Application $app)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array("get_all_groups" => Null), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function getGroupInfo(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $groupId = $id;
            $response = Connector::send_receive($app, array("get_group_info" => $groupId), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function leaveGroups(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $groupId = $id;
            $response = Connector::send_receive($app, array("leave_groups" => array("groups" => array($groupId))), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function getInviteLink(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $groupId = $id;
            $response = Connector::send_receive($app, array('get_group_invite_link' => $groupId), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function revokeInviteLink(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $groupId = $id;
            $response = Connector::send_receive($app, array('revoke_group_invite_link' => $groupId), 'control', $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function getGroupIcon(Request $request, Application $app, $id)
    {
        $format = $request->query->get('format');
        if ($format == 'link') {
            return $this->getGroupIconUrl($request, $app, $id);
        } else {
            return $this->getGroupIconData($request, $app, $id);
        }
    }

    private function getGroupIconUrl(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array("get_group_icon_url" => $id), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $payloadArray = (array)$payload;
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    private function getGroupIconData(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $response = Connector::send_receive($app, array("get_group_icon" => $id), "control", $errors, $app['monolog']);
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
            $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "groupIcon.jpg");
            $response->headers->set("Content-Disposition", $disposition);
            $response->headers->set("Content-Type", "image/jpeg");
            return $response;
        }
    }

    public function setGroupIcon(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_CREATED;
        do {
            $headerContentLength = $request->headers->get('Content-Length');
            $actualContent = $request->getContent();
            $actualContentLength = strlen($actualContent);
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
            $response = Connector::send_receive($app, array("set_group_icon" => array("group" => $id, "Content-Raw" => $actualContent)), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function deleteGroupIcon(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $groupId = $id;
            $response = Connector::send_receive($app, array("delete_group_icon" => $groupId), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function addGroupAdmins(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $wa_ids = Util::getMandatoryParam($request, $errors, "wa_ids");
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $app["monolog"]->debug("waid: ", [$wa_ids]);
            $response = Connector::send_receive($app, array("add_group_admins" => array("group" => $id, "participants" => $wa_ids)), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function removeGroupAdmins(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $wa_ids = Util::getMandatoryParam($request, $errors, "wa_ids");
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $app["monolog"]->debug("waid: ", [$wa_ids]);
            $response = Connector::send_receive($app, array("remove_group_admins" => array("group" => $id, "participants" => $wa_ids)), "control", $errors, $app['monolog']);
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        $post = Util::genResponse($meta, $payload, $errors, $app);
        return $app->json($post, $respCode);
    }

    public function removeParticipants(Request $request, Application $app, $id)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            $wa_ids = Util::getMandatoryParam($request, $errors, "wa_ids");
            if ($errors->hasError()) {
                $respCode = Response::HTTP_BAD_REQUEST;
                break;
            }
            $app["monolog"]->debug("waid: ", [$wa_ids]);
            $response = Connector::send_receive($app, array("remove_group_participants" => array("group" => $id, "participants" => $wa_ids)), "control", $errors, $app['monolog']);
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