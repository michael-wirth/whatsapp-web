<?php
 namespace Symfony\Component\Security\Http\Logout; use Symfony\Component\Security\Core\Authentication\Token\TokenInterface; use Symfony\Component\HttpFoundation\Response; use Symfony\Component\HttpFoundation\Request; class CookieClearingLogoutHandler implements LogoutHandlerInterface { private $cookies; public function __construct(array $cookies) { $this->cookies = $cookies; } public function logout(Request $request, Response $response, TokenInterface $token) { foreach ($this->cookies as $cookieName => $cookieData) { $response->headers->clearCookie($cookieName, $cookieData['path'], $cookieData['domain']); } } } 