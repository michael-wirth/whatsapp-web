<?php
 namespace Symfony\Component\Security\Csrf\Tests\TokenGenerator; use PHPUnit\Framework\TestCase; use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator; class UriSafeTokenGeneratorTest extends TestCase { const ENTROPY = 1000; private static $bytes; private $generator; public static function setUpBeforeClass() { self::$bytes = base64_decode('aMf+Tct/RLn2WQ=='); } protected function setUp() { $this->generator = new UriSafeTokenGenerator(self::ENTROPY); } protected function tearDown() { $this->generator = null; } public function testGenerateToken() { $token = $this->generator->generateToken(); $this->assertTrue(ctype_print($token), 'is printable'); $this->assertStringNotMatchesFormat('%S+%S', $token, 'is URI safe'); $this->assertStringNotMatchesFormat('%S/%S', $token, 'is URI safe'); $this->assertStringNotMatchesFormat('%S=%S', $token, 'is URI safe'); } } 