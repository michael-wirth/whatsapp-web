<?php
 namespace Symfony\Component\Security\Core\Tests\Role; use PHPUnit\Framework\TestCase; use Symfony\Component\Security\Core\Role\SwitchUserRole; class SwitchUserRoleTest extends TestCase { public function testGetSource() { $role = new SwitchUserRole('FOO', $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')->getMock()); $this->assertSame($token, $role->getSource()); } public function testGetRole() { $role = new SwitchUserRole('FOO', $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')->getMock()); $this->assertEquals('FOO', $role->getRole()); } } 