<?php
 namespace Doctrine\Tests\DBAL\Platforms; use Doctrine\DBAL\Platforms\PostgreSQL92Platform; class PostgreSQL92PlatformTest extends AbstractPostgreSqlPlatformTestCase { public function createPlatform() { return new PostgreSQL92Platform(); } public function testHasNativeJsonType() { $this->assertTrue($this->_platform->hasNativeJsonType()); } public function testReturnsJsonTypeDeclarationSQL() { $this->assertSame('JSON', $this->_platform->getJsonTypeDeclarationSQL(array())); } public function testReturnsSmallIntTypeDeclarationSQL() { $this->assertSame( 'SMALLSERIAL', $this->_platform->getSmallIntTypeDeclarationSQL(array('autoincrement' => true)) ); $this->assertSame( 'SMALLINT', $this->_platform->getSmallIntTypeDeclarationSQL(array('autoincrement' => false)) ); $this->assertSame( 'SMALLINT', $this->_platform->getSmallIntTypeDeclarationSQL(array()) ); } public function testInitializesJsonTypeMapping() { $this->assertTrue($this->_platform->hasDoctrineTypeMappingFor('json')); $this->assertEquals('json_array', $this->_platform->getDoctrineTypeMapping('json')); } public function testReturnsCloseActiveDatabaseConnectionsSQL() { $this->assertSame( "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'foo'", $this->_platform->getCloseActiveDatabaseConnectionsSQL('foo') ); } } 