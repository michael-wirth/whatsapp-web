<?php
 namespace Silex\Provider; use Pimple\Container; use Pimple\ServiceProviderInterface; use Silex\Api\EventListenerProviderInterface; use Silex\ExceptionHandler; use Symfony\Component\EventDispatcher\EventDispatcherInterface; class ExceptionHandlerServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface { public function register(Container $app) { $app['exception_handler'] = function ($app) { return new ExceptionHandler($app['debug']); }; } public function subscribe(Container $app, EventDispatcherInterface $dispatcher) { if (isset($app['exception_handler'])) { $dispatcher->addSubscriber($app['exception_handler']); } } } 