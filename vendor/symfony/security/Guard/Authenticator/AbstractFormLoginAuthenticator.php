<?php
 namespace Symfony\Component\Security\Guard\Authenticator; use Symfony\Component\HttpFoundation\Session\SessionInterface; use Symfony\Component\Security\Guard\AbstractGuardAuthenticator; use Symfony\Component\HttpFoundation\RedirectResponse; use Symfony\Component\HttpFoundation\Request; use Symfony\Component\Security\Core\Authentication\Token\TokenInterface; use Symfony\Component\Security\Core\Exception\AuthenticationException; use Symfony\Component\Security\Core\Security; use Symfony\Component\Security\Http\Util\TargetPathTrait; abstract class AbstractFormLoginAuthenticator extends AbstractGuardAuthenticator { use TargetPathTrait; abstract protected function getLoginUrl(); public function onAuthenticationFailure(Request $request, AuthenticationException $exception) { if ($request->getSession() instanceof SessionInterface) { $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception); } $url = $this->getLoginUrl(); return new RedirectResponse($url); } public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey) { @trigger_error(sprintf('The AbstractFormLoginAuthenticator::onAuthenticationSuccess() implementation was deprecated in Symfony 3.1 and will be removed in Symfony 4.0. You should implement this method yourself in %s and remove getDefaultSuccessRedirectUrl().', get_class($this)), E_USER_DEPRECATED); if (!method_exists($this, 'getDefaultSuccessRedirectUrl')) { throw new \Exception(sprintf('You must implement onAuthenticationSuccess() or getDefaultSuccessRedirectUrl() in %s.', get_class($this))); } $targetPath = null; if ($request->getSession() instanceof SessionInterface) { $targetPath = $this->getTargetPath($request->getSession(), $providerKey); } if (!$targetPath) { $targetPath = $this->getDefaultSuccessRedirectUrl(); } return new RedirectResponse($targetPath); } public function supportsRememberMe() { return true; } public function start(Request $request, AuthenticationException $authException = null) { $url = $this->getLoginUrl(); return new RedirectResponse($url); } } 