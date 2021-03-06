<?php

namespace WhatsApp\User;

use Doctrine\DBAL\Connection;
use WhatsApp\Constants;

class UserStorage
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

    public function createUser($username, $password, $roles)
    {
        $this->conn->insert($this->table, array(Constants::KEY_USERNAME => $username, Constants::KEY_PASSWORD => $password, Constants::KEY_ROLES => $roles));
        $this->logger->info("Created user=" . $username);
        return;
    }

    public function updateUser($username, $password)
    {
        $count = $this->conn->update($this->table, array(Constants::KEY_PASSWORD => $password), array(Constants::KEY_USERNAME => $username));
        $this->logger->info("Updated user=" . $username . ", count=" . $count);
        return $count;
    }

    public function deleteUser($username)
    {
        $count = $this->conn->delete($this->table, array(Constants::KEY_USERNAME => $username));
        $this->logger->info("Deleted user=" . $username . ", count=" . $count);
        return $count;
    }

    public function getUser($username)
    {
        $sth = $this->conn->executeQuery('SELECT ' . Constants::KEY_ROLES . ' FROM ' . $this->table . ' WHERE ' . Constants::KEY_USERNAME . ' = ?', array($username));
        $userData = $sth->fetch(\PDO::FETCH_OBJ);
        if (empty($userData)) {
            return null;
        }
        $this->logger->info("Retrieved user=" . $username . ", db_response=" . json_encode($userData));
        return $userData;
    }
}