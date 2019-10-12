<?php
 namespace Symfony\Component\Security\Csrf\Tests; use PHPUnit\Framework\TestCase; use Symfony\Component\Security\Csrf\CsrfToken; use Symfony\Component\Security\Csrf\CsrfTokenManager; class CsrfTokenManagerTest extends TestCase { private $generator; private $storage; private $manager; protected function setUp() { $this->generator = $this->getMockBuilder('Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface')->getMock(); $this->storage = $this->getMockBuilder('Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface')->getMock(); $this->manager = new CsrfTokenManager($this->generator, $this->storage); } protected function tearDown() { $this->generator = null; $this->storage = null; $this->manager = null; } public function testGetNonExistingToken() { $this->storage->expects($this->once()) ->method('hasToken') ->with('token_id') ->will($this->returnValue(false)); $this->generator->expects($this->once()) ->method('generateToken') ->will($this->returnValue('TOKEN')); $this->storage->expects($this->once()) ->method('setToken') ->with('token_id', 'TOKEN'); $token = $this->manager->getToken('token_id'); $this->assertInstanceOf('Symfony\Component\Security\Csrf\CsrfToken', $token); $this->assertSame('token_id', $token->getId()); $this->assertSame('TOKEN', $token->getValue()); } public function testUseExistingTokenIfAvailable() { $this->storage->expects($this->once()) ->method('hasToken') ->with('token_id') ->will($this->returnValue(true)); $this->storage->expects($this->once()) ->method('getToken') ->with('token_id') ->will($this->returnValue('TOKEN')); $token = $this->manager->getToken('token_id'); $this->assertInstanceOf('Symfony\Component\Security\Csrf\CsrfToken', $token); $this->assertSame('token_id', $token->getId()); $this->assertSame('TOKEN', $token->getValue()); } public function testRefreshTokenAlwaysReturnsNewToken() { $this->storage->expects($this->never()) ->method('hasToken'); $this->generator->expects($this->once()) ->method('generateToken') ->will($this->returnValue('TOKEN')); $this->storage->expects($this->once()) ->method('setToken') ->with('token_id', 'TOKEN'); $token = $this->manager->refreshToken('token_id'); $this->assertInstanceOf('Symfony\Component\Security\Csrf\CsrfToken', $token); $this->assertSame('token_id', $token->getId()); $this->assertSame('TOKEN', $token->getValue()); } public function testMatchingTokenIsValid() { $this->storage->expects($this->once()) ->method('hasToken') ->with('token_id') ->will($this->returnValue(true)); $this->storage->expects($this->once()) ->method('getToken') ->with('token_id') ->will($this->returnValue('TOKEN')); $this->assertTrue($this->manager->isTokenValid(new CsrfToken('token_id', 'TOKEN'))); } public function testNonMatchingTokenIsNotValid() { $this->storage->expects($this->once()) ->method('hasToken') ->with('token_id') ->will($this->returnValue(true)); $this->storage->expects($this->once()) ->method('getToken') ->with('token_id') ->will($this->returnValue('TOKEN')); $this->assertFalse($this->manager->isTokenValid(new CsrfToken('token_id', 'FOOBAR'))); } public function testNonExistingTokenIsNotValid() { $this->storage->expects($this->once()) ->method('hasToken') ->with('token_id') ->will($this->returnValue(false)); $this->storage->expects($this->never()) ->method('getToken'); $this->assertFalse($this->manager->isTokenValid(new CsrfToken('token_id', 'FOOBAR'))); } public function testRemoveToken() { $this->storage->expects($this->once()) ->method('removeToken') ->with('token_id') ->will($this->returnValue('REMOVED_TOKEN')); $this->assertSame('REMOVED_TOKEN', $this->manager->removeToken('token_id')); } } 