<?php
 namespace Symfony\Component\Security\Http\Authentication; use Symfony\Component\HttpFoundation\Request; use Symfony\Component\HttpKernel\HttpKernelInterface; use Psr\Log\LoggerInterface; use Symfony\Component\Security\Core\Exception\AuthenticationException; use Symfony\Component\Security\Core\Security; use Symfony\Component\Security\Http\HttpUtils; use Symfony\Component\Security\Http\ParameterBagUtils; class DefaultAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface { protected $httpKernel; protected $httpUtils; protected $logger; protected $options; protected $defaultOptions = array( 'failure_path' => null, 'failure_forward' => false, 'login_path' => '/login', 'failure_path_parameter' => '_failure_path', ); public function __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils, array $options = array(), LoggerInterface $logger = null) { $this->httpKernel = $httpKernel; $this->httpUtils = $httpUtils; $this->logger = $logger; $this->setOptions($options); } public function getOptions() { return $this->options; } public function setOptions(array $options) { $this->options = array_merge($this->defaultOptions, $options); } public function onAuthenticationFailure(Request $request, AuthenticationException $exception) { if ($failureUrl = ParameterBagUtils::getRequestParameterValue($request, $this->options['failure_path_parameter'])) { $this->options['failure_path'] = $failureUrl; } if (null === $this->options['failure_path']) { $this->options['failure_path'] = $this->options['login_path']; } if ($this->options['failure_forward']) { if (null !== $this->logger) { $this->logger->debug('Authentication failure, forward triggered.', array('failure_path' => $this->options['failure_path'])); } $subRequest = $this->httpUtils->createRequest($request, $this->options['failure_path']); $subRequest->attributes->set(Security::AUTHENTICATION_ERROR, $exception); return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST); } if (null !== $this->logger) { $this->logger->debug('Authentication failure, redirect triggered.', array('failure_path' => $this->options['failure_path'])); } $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception); return $this->httpUtils->createRedirectResponse($request, $this->options['failure_path']); } } 