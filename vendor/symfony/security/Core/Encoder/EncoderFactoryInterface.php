<?php
 namespace Symfony\Component\Security\Core\Encoder; use Symfony\Component\Security\Core\User\UserInterface; interface EncoderFactoryInterface { public function getEncoder($user); } 