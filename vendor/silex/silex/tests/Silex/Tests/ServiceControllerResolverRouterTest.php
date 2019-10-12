<?php
 namespace Silex\Tests; use Silex\Application; use Silex\Provider\ServiceControllerServiceProvider; use Symfony\Component\HttpFoundation\Request; class ServiceControllerResolverRouterTest extends RouterTest { public function testServiceNameControllerSyntax() { $app = new Application(); $app->register(new ServiceControllerServiceProvider()); $app['service_name'] = function () { return new MyController(); }; $app->get('/bar', 'service_name:getBar'); $this->checkRouteResponse($app, '/bar', 'bar'); } protected function checkRouteResponse(Application $app, $path, $expectedContent, $method = 'get', $message = null) { $request = Request::create($path, $method); $response = $app->handle($request); $this->assertEquals($expectedContent, $response->getContent(), $message); } } 