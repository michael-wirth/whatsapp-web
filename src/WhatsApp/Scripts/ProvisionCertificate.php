<?php
if ($argc != 4) {
    echo "Usage: {$argv[1]} <http_root> <hostname> <source_cert_dir>";
    exit(1);
}
require_once $argv[1] . '/vendor/autoload.php';
require_once $argv[1] . '/src/WhatsApp/Constants.php';
require_once $argv[1] . '/src/WhatsApp/Database/DBUtil.php';
require_once $argv[1] . '/src/WhatsApp/Certificate/CertificateStorage.php';

use WhatsApp\Certificate\CertificateStorage;
use WhatsApp\Constants;
use WhatsApp\Database\DBUtil;

$provision = new ProvisionCertificate($argv[2], $argv[3]);
try {
    if (!$provision->checkCertificateExists()) {
        echo "Uploading certificate to db\n";
        $provision->uploadCertificate();
    }
    echo "Downloading certificate from db\n";
    $provision->downloadCertificate();
    $provision->rewriteCertificate();
    exit(0);
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

class ProvisionCertificate
{
    private $hostname;
    private $store;
    private $source_cert_dir;

    public function __construct($hostname, $source_cert_dir)
    {
        $this->hostname = $hostname;
        $this->source_cert_dir = $source_cert_dir;
        $dbUtil = new DBUtil(null);
        $dbConfig = DBUtil::getDatabaseConfigFromEnv(Constants::WEB_DB_NAME);
        $conn = $dbUtil->createDBConnection($dbConfig);
        $this->store = new CertificateStorage($conn);
    }

    public function checkCertificateExists()
    {
        return $this->store->certificateExists($this->hostname);
    }

    public function uploadCertificate()
    {
        $cert_file = $this->source_cert_dir . '/' . Constants::CERT_WWW_FILENAME;
        $cert = file_get_contents($cert_file);
        if ($cert === false) {
            throw new \Exception("failed to read " . Constants::CERT_FILE_STAGING);
        }
        $cert_ca_file = $this->source_cert_dir . '/' . Constants::CERT_CA_FILENAME;
        $ca_cert = file_get_contents($cert_ca_file);
        if ($ca_cert === false) {
            throw new \Exception("failed to read " . Constants::CERT_CA_FILE_STAGING);
        }
        $this->store->insertCertificate($ca_cert, $cert, $this->hostname);
    }

    public function downloadCertificate()
    {
        $result = $this->store->getCertificate($this->hostname);
        $cert_ca_file = Constants::CERT_DIR_INSTALLED . Constants::CERT_CA_FILENAME;
        if (!file_put_contents($cert_ca_file, $result[Constants::KEY_CA_CERT])) {
            throw new \Exception("failed to install " . $cert_ca_file);
        }
        echo "Installed ca cert at " . $cert_ca_file . "\n";
        $cert_file = Constants::CERT_DIR_INSTALLED . Constants::CERT_WWW_FILENAME;
        if (!file_put_contents($cert_file, $result[Constants::KEY_CERT])) {
            throw new \Exception("failed to install " . $cert_file);
        }
        echo "Installed server cert at " . $cert_file . "\n";
    }

    public function rewriteCertificate()
    {
        $cert_ca_file = Constants::CERT_DIR_INSTALLED . Constants::CERT_CA_FILENAME;
        $ca_cert = file_get_contents($cert_ca_file);
        if ($ca_cert === false) {
            throw new \Exception("failed to read " . $cert_ca_file);
        }
        $cert_file = Constants::CERT_DIR_INSTALLED . Constants::CERT_WWW_FILENAME;
        $cert = file_get_contents($cert_file);
        if ($cert === false) {
            throw new \Exception("failed to read " . $cert_file);
        }
        $new_cert = array();
        $sections = explode(Constants::CERT_MARKER_END, $cert);
        $cert_blob = $sections[0] . Constants::CERT_MARKER_END . "\n";
        $server_cert_file = Constants::CERT_DIR_INSTALLED . Constants::CERT_SERVER_FILENAME;
        if (!file_put_contents($server_cert_file, $cert_blob)) {
            throw new \Exception("failed to write " . $server_cert_file);
        }
        if (!file_put_contents($server_cert_file, $ca_cert, FILE_APPEND)) {
            throw new \Exception("failed to append " . $server_cert_file);
        }
        echo "Rewrote cert file at " . $server_cert_file . "\n";
    }
} ?>
