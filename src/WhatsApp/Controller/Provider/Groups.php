<?php

namespace WhatsApp\Controller\Provider;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Groups implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $groups = $app["controllers_factory"];
        $groups->post("/", "WhatsApp\\Controller\\GroupsController::createGroup");
        $groups->put("/{id}", "WhatsApp\\Controller\\GroupsController::updateGroup");
        $groups->get("/", "WhatsApp\\Controller\\GroupsController::getAllGroups");
        $groups->get("/{id}", "WhatsApp\\Controller\\GroupsController::getGroupInfo");
        $groups->post("/{id}/leave", "WhatsApp\\Controller\\GroupsController::leaveGroups");
        $groups->get("/{id}/invite", "WhatsApp\\Controller\\GroupsController::getInviteLink");
        $groups->delete("/{id}/invite", "WhatsApp\\Controller\GroupsController::revokeInviteLink");
        $groups->post("/{id}/icon", "WhatsApp\\Controller\\GroupsController::setGroupIcon");
        $groups->get("/{id}/icon", "WhatsApp\\Controller\\GroupsController::getGroupIcon");
        $groups->delete("/{id}/icon", "WhatsApp\\Controller\\GroupsController::deleteGroupIcon");
        $groups->patch("/{id}/admins", "WhatsApp\\Controller\GroupsController::addGroupAdmins");
        $groups->delete("/{id}/admins", "WhatsApp\\Controller\\GroupsController::removeGroupAdmins");
        $groups->delete("/{id}/participants", "WhatsApp\\Controller\\GroupsController::removeParticipants");
        return $groups;
    }
} 