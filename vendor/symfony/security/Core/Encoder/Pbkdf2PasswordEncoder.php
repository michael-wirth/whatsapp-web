<?php
 namespace Symfony\Component\Security\Core\Encoder; use Symfony\Component\Security\Core\Exception\BadCredentialsException; class Pbkdf2PasswordEncoder extends BasePasswordEncoder { private $algorithm; private $encodeHashAsBase64; private $iterations; private $length; public function __construct($algorithm = 'sha512', $encodeHashAsBase64 = true, $iterations = 1000, $length = 40) { $this->algorithm = $algorithm; $this->encodeHashAsBase64 = $encodeHashAsBase64; $this->iterations = $iterations; $this->length = $length; } public function encodePassword($raw, $salt) { if ($this->isPasswordTooLong($raw)) { throw new BadCredentialsException('Invalid password.'); } if (!in_array($this->algorithm, hash_algos(), true)) { throw new \LogicException(sprintf('The algorithm "%s" is not supported.', $this->algorithm)); } $digest = hash_pbkdf2($this->algorithm, $raw, $salt, $this->iterations, $this->length, true); return $this->encodeHashAsBase64 ? base64_encode($digest) : bin2hex($digest); } public function isPasswordValid($encoded, $raw, $salt) { return !$this->isPasswordTooLong($raw) && $this->comparePasswords($encoded, $this->encodePassword($raw, $salt)); } } 