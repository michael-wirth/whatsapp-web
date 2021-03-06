<?php
 namespace Doctrine\Tests\DBAL\Driver\SQLSrv; use Doctrine\Tests\DbalTestCase; class SQLSrvConnectionTest extends DbalTestCase { private $connectionMock; protected function setUp() { if ( ! extension_loaded('sqlsrv')) { $this->markTestSkipped('sqlsrv is not installed.'); } parent::setUp(); $this->connectionMock = $this->getMockBuilder('Doctrine\DBAL\Driver\SQLSrv\SQLSrvConnection') ->disableOriginalConstructor() ->getMockForAbstractClass(); } public function testDoesNotRequireQueryForServerVersion() { $this->assertFalse($this->connectionMock->requiresQueryForServerVersion()); } } 