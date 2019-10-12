<?php

namespace WhatsApp\Security;

use Doctrine\DBAL\Connection;
use WhatsApp\Constants;

class TokenStorage
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

    public function storeToken($token, $username)
    {
        $this->conn->insert($this->table, array(Constants::KEY_TOKEN => $token, Constants::KEY_USERNAME => $username));
        $this->logger->info("Stored token");
        return;
    }

    public function deleteToken($token)
    {
        $count = $this->conn->delete($this->table, array(Constants::KEY_TOKEN => $token));
        $this->logger->info("Deleted token; count=" . $count);
        return $count;
    }

    public function deleteTokenByUsername($username)
    {
        $count = $this->conn->delete($this->table, array(Constants::KEY_USERNAME => $username));
        $this->logger->info("Deleted token(s); count=" . $count);
        return $count;
    }

    public function getToken($token)
    {
        $query = $this->conn->executeQuery('SELECT ' . Constants::KEY_TOKEN . ' FROM ' . $this->table . ' WHERE ' . Constants::KEY_TOKEN . ' = ?', array($token));
        $token = $query->fetchColumn();
        if (empty($token)) {
            return null;
        }
        return $token;
    }

    public function getTokensForUser($username)
    {
        $query = $this->conn->executeQuery('SELECT ' . Constants::KEY_TOKEN . ' FROM ' . $this->table . ' WHERE ' . Constants::KEY_USERNAME . ' = ? ', array($username));
        return $query->fetchAll();
    }

    public function userOwnsToken($token, $username)
    {
        $query = $this->conn->executeQuery('SELECT * FROM ' . $this->table . ' WHERE ' . Constants::KEY_TOKEN . ' = ? ' . ' AND ' . Constants::KEY_USERNAME . ' = ? ', array($token, $username));
        $result = $query->fetchAll();
        return empty($result) ? false : true;
    }
} 