<?php
 namespace Symfony\Component\Security\Http\Firewall; use Symfony\Component\HttpFoundation\Request; use Symfony\Component\HttpFoundation\Response; use Symfony\Component\HttpKernel\Event\GetResponseEvent; use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface; use Symfony\Component\Security\Core\Exception\LogoutException; use Symfony\Component\Security\Csrf\CsrfToken; use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface; use Symfony\Component\Security\Http\HttpUtils; use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface; use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface; use Symfony\Component\Security\Http\ParameterBagUtils; class LogoutListener implements ListenerInterface { private $tokenStorage; private $options; private $handlers; private $successHandler; private $httpUtils; private $csrfTokenManager; public function __construct(TokenStorageInterface $tokenStorage, HttpUtils $httpUtils, LogoutSuccessHandlerInterface $successHandler, array $options = array(), CsrfTokenManagerInterface $csrfTokenManager = null) { $this->tokenStorage = $tokenStorage; $this->httpUtils = $httpUtils; $this->options = array_merge(array( 'csrf_parameter' => '_csrf_token', 'csrf_token_id' => 'logout', 'logout_path' => '/logout', ), $options); $this->successHandler = $successHandler; $this->csrfTokenManager = $csrfTokenManager; $this->handlers = array(); } public function addHandler(LogoutHandlerInterface $handler) { $this->handlers[] = $handler; } public function handle(GetResponseEvent $event) { $request = $event->getRequest(); if (!$this->requiresLogout($request)) { return; } if (null !== $this->csrfTokenManager) { $csrfToken = ParameterBagUtils::getRequestParameterValue($request, $this->options['csrf_parameter']); if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken($this->options['csrf_token_id'], $csrfToken))) { throw new LogoutException('Invalid CSRF token.'); } } $response = $this->successHandler->onLogoutSuccess($request); if (!$response instanceof Response) { throw new \RuntimeException('Logout Success Handler did not return a Response.'); } if ($token = $this->tokenStorage->getToken()) { foreach ($this->handlers as $handler) { $handler->logout($request, $response, $token); } } $this->tokenStorage->setToken(null); $event->setResponse($response); } protected function requiresLogout(Request $request) { return isset($this->options['logout_path']) && $this->httpUtils->checkRequestPath($request, $this->options['logout_path']); } } 