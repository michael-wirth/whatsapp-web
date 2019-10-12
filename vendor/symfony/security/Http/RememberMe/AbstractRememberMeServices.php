<?php
 namespace Symfony\Component\Security\Http\RememberMe; use Symfony\Component\Security\Core\Exception\AuthenticationException; use Symfony\Component\Security\Core\User\UserInterface; use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken; use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface; use Symfony\Component\Security\Core\Authentication\Token\TokenInterface; use Symfony\Component\Security\Core\Exception\UnsupportedUserException; use Symfony\Component\Security\Core\Exception\UsernameNotFoundException; use Symfony\Component\Security\Core\Exception\CookieTheftException; use Symfony\Component\HttpFoundation\Response; use Symfony\Component\HttpFoundation\Request; use Symfony\Component\HttpFoundation\Cookie; use Psr\Log\LoggerInterface; use Symfony\Component\Security\Http\ParameterBagUtils; abstract class AbstractRememberMeServices implements RememberMeServicesInterface, LogoutHandlerInterface { const COOKIE_DELIMITER = ':'; protected $logger; protected $options = array( 'secure' => false, 'httponly' => true, ); private $providerKey; private $secret; private $userProviders; public function __construct(array $userProviders, $secret, $providerKey, array $options = array(), LoggerInterface $logger = null) { if (empty($secret)) { throw new \InvalidArgumentException('$secret must not be empty.'); } if (empty($providerKey)) { throw new \InvalidArgumentException('$providerKey must not be empty.'); } if (0 === count($userProviders)) { throw new \InvalidArgumentException('You must provide at least one user provider.'); } $this->userProviders = $userProviders; $this->secret = $secret; $this->providerKey = $providerKey; $this->options = array_merge($this->options, $options); $this->logger = $logger; } public function getRememberMeParameter() { return $this->options['remember_me_parameter']; } public function getSecret() { return $this->secret; } final public function autoLogin(Request $request) { if (null === $cookie = $request->cookies->get($this->options['name'])) { return; } if (null !== $this->logger) { $this->logger->debug('Remember-me cookie detected.'); } $cookieParts = $this->decodeCookie($cookie); try { $user = $this->processAutoLoginCookie($cookieParts, $request); if (!$user instanceof UserInterface) { throw new \RuntimeException('processAutoLoginCookie() must return a UserInterface implementation.'); } if (null !== $this->logger) { $this->logger->info('Remember-me cookie accepted.'); } return new RememberMeToken($user, $this->providerKey, $this->secret); } catch (CookieTheftException $e) { $this->cancelCookie($request); throw $e; } catch (UsernameNotFoundException $e) { if (null !== $this->logger) { $this->logger->info('User for remember-me cookie not found.'); } } catch (UnsupportedUserException $e) { if (null !== $this->logger) { $this->logger->warning('User class for remember-me cookie not supported.'); } } catch (AuthenticationException $e) { if (null !== $this->logger) { $this->logger->debug('Remember-Me authentication failed.', array('exception' => $e)); } } $this->cancelCookie($request); } public function logout(Request $request, Response $response, TokenInterface $token) { $this->cancelCookie($request); } final public function loginFail(Request $request) { $this->cancelCookie($request); $this->onLoginFail($request); } final public function loginSuccess(Request $request, Response $response, TokenInterface $token) { $this->cancelCookie($request); if (!$token->getUser() instanceof UserInterface) { if (null !== $this->logger) { $this->logger->debug('Remember-me ignores token since it does not contain a UserInterface implementation.'); } return; } if (!$this->isRememberMeRequested($request)) { if (null !== $this->logger) { $this->logger->debug('Remember-me was not requested.'); } return; } if (null !== $this->logger) { $this->logger->debug('Remember-me was requested; setting cookie.'); } $request->attributes->remove(self::COOKIE_ATTR_NAME); $this->onLoginSuccess($request, $response, $token); } abstract protected function processAutoLoginCookie(array $cookieParts, Request $request); protected function onLoginFail(Request $request) { } abstract protected function onLoginSuccess(Request $request, Response $response, TokenInterface $token); final protected function getUserProvider($class) { foreach ($this->userProviders as $provider) { if ($provider->supportsClass($class)) { return $provider; } } throw new UnsupportedUserException(sprintf('There is no user provider that supports class "%s".', $class)); } protected function decodeCookie($rawCookie) { return explode(self::COOKIE_DELIMITER, base64_decode($rawCookie)); } protected function encodeCookie(array $cookieParts) { foreach ($cookieParts as $cookiePart) { if (false !== strpos($cookiePart, self::COOKIE_DELIMITER)) { throw new \InvalidArgumentException(sprintf('$cookieParts should not contain the cookie delimiter "%s"', self::COOKIE_DELIMITER)); } } return base64_encode(implode(self::COOKIE_DELIMITER, $cookieParts)); } protected function cancelCookie(Request $request) { if (null !== $this->logger) { $this->logger->debug('Clearing remember-me cookie.', array('name' => $this->options['name'])); } $request->attributes->set(self::COOKIE_ATTR_NAME, new Cookie($this->options['name'], null, 1, $this->options['path'], $this->options['domain'], $this->options['secure'], $this->options['httponly'])); } protected function isRememberMeRequested(Request $request) { if (true === $this->options['always_remember_me']) { return true; } $parameter = ParameterBagUtils::getRequestParameterValue($request, $this->options['remember_me_parameter']); if (null === $parameter && null !== $this->logger) { $this->logger->debug('Did not send remember-me cookie.', array('parameter' => $this->options['remember_me_parameter'])); } return $parameter === 'true' || $parameter === 'on' || $parameter === '1' || $parameter === 'yes' || $parameter === true; } } 