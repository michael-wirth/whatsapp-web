<?php

namespace WhatsApp\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use WhatsApp\Constants;

class UserProvider implements UserProviderInterface
{
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf("Instances of \"%s\" are not supported.", get_class($user)));
        }
        return $this->loadUserByUsername($user->getUsername());
    }

    public function loadUserByUsername($username)
    {
        $stmt = $this->conn->executeQuery('SELECT ' . Constants::KEY_USERNAME . ',' . Constants::KEY_PASSWORD . ',' . Constants::KEY_ROLES . ' FROM ' . Constants::TABLE_USERS . ' WHERE ' . Constants::KEY_USERNAME . ' = ?', array($username));
        if (!$user = $stmt->fetch()) {
            throw new UsernameNotFoundException(sprintf("Username \"%s\" does not exist.", $username));
        }
        return new User($user[Constants::KEY_USERNAME], $user[Constants::KEY_PASSWORD], explode(',', $user[Constants::KEY_ROLES]), true, true, true, true);
    }

    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
} 