<?php

namespace WhatsApp\Security;

class Crypto
{
    public static function genKeySha1($secret, $salt, $iter, $keyLen)
    {
        return hash_pbkdf2('sha1', $secret, $salt, $iter, $keyLen, true);
    }

    public static function genKeySha256($secret, $salt, $iter, $keyLen)
    {
        return hash_pbkdf2('sha256', $secret, $salt, $iter, $keyLen, true);
    }
}

class CryptoUtils
{
    const SALT_LEN = 8;
    private $logger_;

    public function __construct($logger)
    {
        $this->logger_ = $logger;
    }

    public function getKey($id, $keyLen = 32, $iter = 10000, $value = null)
    {
        if (apcu_exists($id)) {
            return apcu_fetch($id);
        }
        if ($value === null) {
            $value = getenv($id);
            if (!$value) {
                return null;
            }
        }
        $salt = Crypto::genKeySha256($value, null, $iter, self::SALT_LEN);
        $key = Crypto::genKeySha256($value, $salt, $iter, $keyLen);
        $stored = apcu_store($id, $key);
        if ($stored === false) {
            $this->logger_->error("Unable to store key to cache");
        } else {
            $this->logger_->info("Key stored to cache");
        }
        return $key;
    }
} 