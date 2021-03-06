<?php
namespace WhatsApp\Common;

use WhatsApp\Constants;
use WhatsApp\Database\ContactStore;

class Whitelist
{
    public static function recipient_ok($app, $to, $is_group)
    {
        if (Util::isSandboxMode()) {
            if (!$is_group) {
                $contactStore = new ContactStore($app['db'], Constants::TABLE_CONTACTS, $app['monolog']);
                $contact = $contactStore->getContact($to);
                if (is_null($contact)) {
                    return false;
                }
                return ($contact->joined === "1");
            }
            return true;
        }
        return true;
    }
} ?>
