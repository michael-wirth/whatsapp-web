<?php

use GeckoPackages\Silex\Services\Config\ConfigServiceProvider;

$app->register(new ConfigServiceProvider(), array('config.dir' => __DIR__ . '/config', 'config.format' => '%key%.%env%.yml', 'config.env' => getenv('WA_ENT_ENV') ?: 'prod',));
$app->register(new ConfigServiceProvider('config.run'), array('config.run.dir' => $app['config']['app']['data']['dir'], 'config.run.db_config_file' => $app['config']['app']['data']['db_config_file'], 'config.run.format' => '%key%.yml',));