<?php

namespace WhatsApp\Security;

class SecurePayload
{
    const TIME_ALLOWANCE = 5;
    private $logger_;
    private $nonce_;
    private $secretKey_;

    public function __construct($logger, $secretKey, $nonceB64 = null)
    {
        $this->logger_ = $logger;
        if ($nonceB64 === null) {
            $this->nonce_ = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        } else {
            $this->nonce_ = base64_decode($nonceB64);
        }
        $this->secretKey_ = $secretKey;
    }

    public function nonce()
    {
        return base64_encode($this->nonce_);
    }

    public function encrypt($payload)
    {
        $now = time();
        $encPayload = base64_encode(sodium_crypto_secretbox($now . ";" . $payload, $this->nonce_, $this->secretKey_));
        if (strlen($encPayload) == 0) {
            $this->logger_->info("Unable to encrypt payload");
            return null;
        }
        return $encPayload;
    }

    public function decrypt($encPayload)
    {
        $payload = sodium_crypto_secretbox_open(base64_decode($encPayload), $this->nonce_, $this->secretKey_);
        if (strlen($payload) == 0) {
            $this->logger_->info("Unable to decrypt payload");
            return null;
        }
        $ts = substr($payload, 0, strpos($payload, ";"));
        if ($this->isReplay($ts)) {
            return null;
        }
        return substr($payload, strpos($payload, ";") + 1);
    }

    public function isReplay($ts)
    {
        $now = time();
        if (($ts < $now - SecurePayload::TIME_ALLOWANCE) || ($ts > $now + SecurePayload::TIME_ALLOWANCE)) {
            $this->logger_->info("Possible replay attack or time not in sync. Timestamp=$ts, Now=$now");
            return true;
        }
        return false;
    }
} 