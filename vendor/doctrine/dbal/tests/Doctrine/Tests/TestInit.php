<?php
 error_reporting(E_ALL | E_STRICT); date_default_timezone_set('UTC'); if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) { $classLoader = require __DIR__ . '/../../../vendor/autoload.php'; } elseif (file_exists(__DIR__ . '/../../../../../autoload.php')) { $classLoader = require __DIR__ . '/../../../../../autoload.php'; } else { throw new Exception('Can\'t find autoload.php. Did you install dependencies via Composer?'); } $classLoader->add('Doctrine\\Tests\\', __DIR__ . '/../../'); unset($classLoader); 