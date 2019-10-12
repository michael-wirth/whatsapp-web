<?php
 namespace Silex\Tests; use PHPUnit\Framework\TestCase; use Symfony\Component\HttpFoundation\Request; use Silex\Provider\Routing\LazyRequestMatcher; class LazyRequestMatcherTest extends TestCase { public function testUserMatcherIsCreatedLazily() { $callCounter = 0; $requestMatcher = $this->getMockBuilder('Symfony\Component\Routing\Matcher\RequestMatcherInterface')->getMock(); $matcher = new LazyRequestMatcher(function () use ($requestMatcher, &$callCounter) { ++$callCounter; return $requestMatcher; }); $this->assertEquals(0, $callCounter); $request = Request::create('path'); $matcher->matchRequest($request); $this->assertEquals(1, $callCounter); } public function testThatCanInjectRequestMatcherOnly() { $matcher = new LazyRequestMatcher(function () { return 'someMatcher'; }); $request = Request::create('path'); $matcher->matchRequest($request); } public function testMatchIsProxy() { $request = Request::create('path'); $matcher = $this->getMockBuilder('Symfony\Component\Routing\Matcher\RequestMatcherInterface')->getMock(); $matcher->expects($this->once()) ->method('matchRequest') ->with($request) ->will($this->returnValue('matcherReturnValue')); $matcher = new LazyRequestMatcher(function () use ($matcher) { return $matcher; }); $result = $matcher->matchRequest($request); $this->assertEquals('matcherReturnValue', $result); } } 