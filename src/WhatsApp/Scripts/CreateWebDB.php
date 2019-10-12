<?php
if ($argc != 3) {
    echo "Usage: {$argv[0]} <http_root> <db_cfg_dir>";
    exit(1);
}
require_once $argv[1] . '/vendor/autoload.php';
require_once $argv[1] . '/src/WhatsApp/Constants.php';
require_once $argv[1] . '/src/WhatsApp/Database/DBUtil.php';

use Symfony\Component\Yaml\Yaml;
use WhatsApp\Constants;
use WhatsApp\Database\DBUtil;

try {
    $dbUtil = new DBUtil(null);
    $dbConfig = DBUtil::getDatabaseConfigFromEnv(Constants::WEB_DB_NAME);
    $conn = $dbUtil->createDBConnection($dbConfig);
    echo "\nDatabase " . Constants::WEB_DB_NAME . " created successfully\n";
    $dbUtil->initDatabase($conn);
    echo "Database " . Constants::WEB_DB_NAME . " initialized successfully\n";
    unset($dbConfig['dbname']);
    $cfgdir = $argv[2];
    $yaml = Yaml::dump($dbConfig);
    $retval = file_put_contents($cfgdir . '/database.yml', $yaml);
    if (!$retval) {
        echo "Unable to create configuration file. Check permissions\n";
        exit(1);
    }
    echo "Database settings stored in database.yml file\n";
} catch (\Exception $e) {
    echo "Failed to create web db or store db settings to database.yml file.\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    echo "\n";
    exit(1);
} ?>
