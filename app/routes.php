<?php

use WhatsApp\Controller\Provider\Account;
use WhatsApp\Controller\Provider\Admin;
use WhatsApp\Controller\Provider\Certificates;
use WhatsApp\Controller\Provider\Contacts;
use WhatsApp\Controller\Provider\Groups;
use WhatsApp\Controller\Provider\Health;
use WhatsApp\Controller\Provider\Helper;
use WhatsApp\Controller\Provider\Media;
use WhatsApp\Controller\Provider\Messages;
use WhatsApp\Controller\Provider\Metrics;
use WhatsApp\Controller\Provider\Settings;
use WhatsApp\Controller\Provider\Stats;
use WhatsApp\Controller\Provider\Support;
use WhatsApp\Controller\Provider\Users;

$app->mount("/", new Helper());
$app->mount("/v1/account", new Account());
$app->mount("/v1/admin", new Admin());
$app->mount("/v1/certificates", new Certificates());
$app->mount("/v1/contacts", new Contacts());
$app->mount("/v1/groups", new Groups());
$app->mount("/v1/health", new Health());
$app->mount("/v1/media", new Media());
$app->mount("/v1/messages", new Messages());
$app->mount("/v1/settings", new Settings());
$app->mount("/v1/stats", new Stats());
$app->mount("/v1/support", new Support());
$app->mount("/v1/users", new Users());
$app->mount("/metrics", new Metrics()); ?>
