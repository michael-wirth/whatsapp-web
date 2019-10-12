<?php

namespace WhatsApp\Database;

use Doctrine\DBAL\Connection;
use WhatsApp\Constants;

class ContactStore
{
    private $conn;
    private $table;
    private $logger;

    public function __construct(Connection $conn, $table, $logger)
    {
        $this->conn = $conn;
        $this->table = $table;
        $this->logger = $logger;
    }

    public function getContact($waID)
    {
        $sth = $this->conn->executeQuery('SELECT * FROM ' . $this->table . ' WHERE ' . Constants::KEY_WA_ID . ' = ?', array($waID));
        $contact = $sth->fetch(\PDO::FETCH_OBJ);
        if (empty($contact)) {
            return null;
        }
        $this->logger->info("Retrieved contact=" . $waID . ", db_response=" . json_encode($contact));
        return $contact;
    }
}