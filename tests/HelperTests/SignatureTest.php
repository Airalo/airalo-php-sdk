<?php

namespace Airalo\Tests\HelperTests;

use PHPUnit\Framework\TestCase;
use Airalo\Helpers\Signature;
use ReflectionMethod;

class SignatureTest extends TestCase
{
    private $secret;
    private $signature;

    protected function setUp(): void
    {
        $this->secret = 'test_secret';
        $this->signature = new Signature($this->secret);
    }

    public function testGetSignatureWithStringPayload()
    {
        $payload = 'test_payload';
        $expectedSignature = hash_hmac('sha512', json_encode(json_decode($payload, true)), $this->secret);

        $result = $this->signature->getSignature($payload);

        $this->assertSame($expectedSignature, $result);
    }

    public function testGetSignatureWithArrayPayload()
    {
        $payload = ['key' => 'value'];
        $expectedSignature = hash_hmac('sha512', json_encode($payload), $this->secret);

        $result = $this->signature->getSignature($payload);

        $this->assertSame($expectedSignature, $result);
    }

    public function testGetSignatureWithNullPayload()
    {
        $result = $this->signature->getSignature(null);

        $this->assertNull($result);
    }

    public function testCheckSignatureWithValidSignature()
    {
        $payload = ['key' => 'value'];
        $hash = $this->signature->getSignature($payload);

        $result = $this->signature->checkSignature($hash, $payload);

        $this->assertTrue($result);
    }

    public function testCheckSignatureWithInvalidSignature()
    {
        $payload = ['key' => 'value'];
        $invalidHash = 'invalid_hash';

        $result = $this->signature->checkSignature($invalidHash, $payload);

        $this->assertFalse($result);
    }

    public function testCheckSignatureWithNullHash()
    {
        $payload = ['key' => 'value'];

        $result = $this->signature->checkSignature(null, $payload);

        $this->assertFalse($result);
    }

    public function testCheckSignatureWithNullPayload()
    {
        $hash = $this->signature->getSignature('test_payload');

        $result = $this->signature->checkSignature($hash, null);

        $this->assertFalse($result);
    }

    public function testPreparePayloadWithArray()
    {
        $method = new ReflectionMethod(Signature::class, 'preparePayload');
        $method->setAccessible(true);

        $payload = ['key' => 'value'];
        $expectedResult = json_encode($payload);

        $result = $method->invoke($this->signature, $payload);

        $this->assertSame($expectedResult, $result);
    }

    public function testPreparePayloadWithString()
    {
        $method = new ReflectionMethod(Signature::class, 'preparePayload');
        $method->setAccessible(true);

        $payload = 'test_payload';
        $expectedResult = json_encode(json_decode($payload, true));

        $result = $method->invoke($this->signature, $payload);

        $this->assertSame($expectedResult, $result);
    }

    public function testPreparePayloadWithNull()
    {
        $method = new ReflectionMethod(Signature::class, 'preparePayload');
        $method->setAccessible(true);

        $result = $method->invoke($this->signature, null);

        $this->assertNull($result);
    }

    public function testSignData()
    {
        $method = new ReflectionMethod(Signature::class, 'signData');
        $method->setAccessible(true);

        $payload = 'test_payload';
        $expectedResult = hash_hmac('sha512', $payload, $this->secret);

        $result = $method->invoke($this->signature, $payload);

        $this->assertSame($expectedResult, $result);
    }
}
