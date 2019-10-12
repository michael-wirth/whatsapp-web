<?php
 namespace Symfony\Component\Security\Core\Tests\User; use PHPUnit\Framework\TestCase; use Symfony\Component\Security\Core\User\InMemoryUserProvider; use Symfony\Component\Security\Core\User\User; class InMemoryUserProviderTest extends TestCase { public function testConstructor() { $provider = $this->createProvider(); $user = $provider->loadUserByUsername('fabien'); $this->assertEquals('foo', $user->getPassword()); $this->assertEquals(array('ROLE_USER'), $user->getRoles()); $this->assertFalse($user->isEnabled()); } public function testRefresh() { $user = new User('fabien', 'bar'); $provider = $this->createProvider(); $refreshedUser = $provider->refreshUser($user); $this->assertEquals('foo', $refreshedUser->getPassword()); $this->assertEquals(array('ROLE_USER'), $refreshedUser->getRoles()); $this->assertFalse($refreshedUser->isEnabled()); $this->assertFalse($refreshedUser->isCredentialsNonExpired()); } protected function createProvider() { return new InMemoryUserProvider(array( 'fabien' => array( 'password' => 'foo', 'enabled' => false, 'roles' => array('ROLE_USER'), ), )); } public function testCreateUser() { $provider = new InMemoryUserProvider(); $provider->createUser(new User('fabien', 'foo')); $user = $provider->loadUserByUsername('fabien'); $this->assertEquals('foo', $user->getPassword()); } public function testCreateUserAlreadyExist() { $provider = new InMemoryUserProvider(); $provider->createUser(new User('fabien', 'foo')); $provider->createUser(new User('fabien', 'foo')); } public function testLoadUserByUsernameDoesNotExist() { $provider = new InMemoryUserProvider(); $provider->loadUserByUsername('fabien'); } } 