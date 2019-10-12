<?php
 namespace Symfony\Component\Security\Core\Encoder; interface PasswordEncoderInterface { public function encodePassword($raw, $salt); public function isPasswordValid($encoded, $raw, $salt); } 