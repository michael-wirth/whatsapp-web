<?php
 namespace Symfony\Component\Security\Http\Tests\Session; use PHPUnit\Framework\TestCase; use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy; class SessionAuthenticationStrategyTest extends TestCase { public function testSessionIsNotChanged() { $request = $this->getRequest(); $request->expects($this->never())->method('getSession'); $strategy = new SessionAuthenticationStrategy(SessionAuthenticationStrategy::NONE); $strategy->onAuthentication($request, $this->getToken()); } public function testUnsupportedStrategy() { $request = $this->getRequest(); $request->expects($this->never())->method('getSession'); $strategy = new SessionAuthenticationStrategy('foo'); $strategy->onAuthentication($request, $this->getToken()); } public function testSessionIsMigrated() { $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\SessionInterface')->getMock(); $session->expects($this->once())->method('migrate')->with($this->equalTo(true)); $strategy = new SessionAuthenticationStrategy(SessionAuthenticationStrategy::MIGRATE); $strategy->onAuthentication($this->getRequest($session), $this->getToken()); } public function testSessionIsInvalidated() { $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\SessionInterface')->getMock(); $session->expects($this->once())->method('invalidate'); $strategy = new SessionAuthenticationStrategy(SessionAuthenticationStrategy::INVALIDATE); $strategy->onAuthentication($this->getRequest($session), $this->getToken()); } private function getRequest($session = null) { $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock(); if (null !== $session) { $request->expects($this->any())->method('getSession')->will($this->returnValue($session)); } return $request; } private function getToken() { return $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')->getMock(); } } 