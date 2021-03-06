<?php

namespace WhatsApp\Certificate;

use Doctrine\DBAL\Connection;
use WhatsApp\Constants;
use WhatsApp\Database\DBUtil;

class CertificateStorage
{
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function insertCertificate($ca_cert, $cert, $hostname, $cert_type = Constants::CERT_TYPE_EXTERNAL)
    {
        $driver = DBUtil::getDBDriver($this->conn);
        if ($driver === Constants::DB_DRIVER_MYSQL) {
            $insertText = "INSERT IGNORE INTO ";
        } else if ($driver === Constants::DB_DRIVER_SQLITE) {
            $insertText = "INSERT OR IGNORE INTO ";
        } else if ($driver === Constants::DB_DRIVER_PGSQL) {
            $insertText = "INSERT INTO ";
        } else {
            throw new \Exception("Do not support database driver " . $driver);
        }
        $sqlText = $insertText . Constants::TABLE_CERTS . ' (' . Constants::KEY_HOSTNAME . ', ' . Constants::KEY_CERT . ', ' . Constants::KEY_CA_CERT . ', ' . Constants::KEY_CERT_TYPE . ') VALUES (?, ?, ?, ?)';
        if ($driver === Constants::DB_DRIVER_PGSQL) {
            $sqlText = $sqlText . "  ON CONFLICT DO NOTHING ";
        }
        $sql = $this->conn->prepare($sqlText);
        $sql->bindValue(1, $hostname);
        $sql->bindValue(2, $cert);
        $sql->bindValue(3, $ca_cert);
        $sql->bindValue(4, $cert_type);
        $sql->execute();
    }

    public function updateCertificate($ca_cert, $cert, $hostname, $cert_type = Constants::CERT_TYPE_EXTERNAL)
    {
        $this->conn->executeUpdate('UPDATE ' . Constants::TABLE_CERTS . ' SET ' . Constants::KEY_CA_CERT . ' = ?, ' . Constants::KEY_CERT . ' = ? WHERE ' . Constants::KEY_CERT_TYPE . ' = ? AND ' . Constants::KEY_HOSTNAME . ' = ?', array($ca_cert, $cert, $cert_type, $hostname));
    }

    public function certificateExists($hostname, $cert_type = Constants::CERT_TYPE_EXTERNAL)
    {
        $stmt = $this->conn->executeQuery('SELECT ' . Constants::KEY_HOSTNAME . ' FROM ' . Constants::TABLE_CERTS . ' WHERE ' . Constants::KEY_HOSTNAME . ' = ?' . ' AND ' . Constants::KEY_CERT_TYPE . ' = ?', array($hostname, $cert_type));
        $result = $stmt->fetchAll();
        return !empty($result);
    }

    public function getCertificate($hostname, $cert_type = Constants::CERT_TYPE_EXTERNAL)
    {
        $stmt = $this->conn->executeQuery('SELECT ' . Constants::KEY_CERT . ', ' . Constants::KEY_CA_CERT . ' FROM ' . Constants::TABLE_CERTS . ' WHERE ' . Constants::KEY_HOSTNAME . ' = ?' . ' AND ' . Constants::KEY_CERT_TYPE . ' = ?', array($hostname, $cert_type));
        $result = $stmt->fetchAll();
        if (empty($result)) {
            return null;
        }
        return $result[0];
    }
}