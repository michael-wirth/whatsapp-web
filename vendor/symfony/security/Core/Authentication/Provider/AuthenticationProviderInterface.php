<?php
 namespace Symfony\Component\Security\Core\Authentication\Provider; use Symfony\Component\Security\Core\Authentication\Token\TokenInterface; use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface; interface AuthenticationProviderInterface extends AuthenticationManagerInterface { const USERNAME_NONE_PROVIDED = 'NONE_PROVIDED'; public function supports(TokenInterface $token); } 