<?php
 namespace Symfony\Component\Security\Http\Event; use Symfony\Component\HttpFoundation\Request; use Symfony\Component\EventDispatcher\Event; use Symfony\Component\Security\Core\Authentication\Token\TokenInterface; class InteractiveLoginEvent extends Event { private $request; private $authenticationToken; public function __construct(Request $request, TokenInterface $authenticationToken) { $this->request = $request; $this->authenticationToken = $authenticationToken; } public function getRequest() { return $this->request; } public function getAuthenticationToken() { return $this->authenticationToken; } } 