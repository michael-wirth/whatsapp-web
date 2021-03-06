<?php

use WhatsApp\Constants;
use WhatsApp\Database\DBUtil;

$dbConfig = DBUtil::getDatabaseConfigFromFile($app['config.run.db_config_file'], Constants::WEB_DB_NAME);
$dbConfig['wrapperClass'] = 'WhatsApp\Database\DBConnection';
$dbConfig['pdo'] = DBUtil::getPDO($dbConfig);
$app->register(new \Silex\Provider\DoctrineServiceProvider(), array('db.options' => $dbConfig));
$app['db']->setApplication($app); ?>
