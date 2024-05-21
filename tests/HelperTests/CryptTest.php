<?php

namespace Airalo\Tests\HelperTests;

use PHPUnit\Framework\TestCase;
use Airalo\Helpers\Crypt;
use Airalo\Exceptions\AiraloException;

class CryptTest extends TestCase
{
    private $key;

    protected function setUp(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('The sodium extension is not available.');
        }

        $this->key = sodium_crypto_secretbox_keygen();
    }

    public function testEncryptAndDecrypt()
    {
        $data = 'Hello, World!';
        $encryptedData = Crypt::encrypt($data, $this->key);

        $this->assertNotSame($data, $encryptedData, 'Data should be encrypted.');
        $this->assertTrue(Crypt::isEncrypted($encryptedData), 'Data should be identified as encrypted.');

        $decryptedData = Crypt::decrypt($encryptedData, $this->key);

        $this->assertSame($data, $decryptedData, 'Decrypted data should match original data.');
    }

    public function testEncryptWithInvalidKey()
    {
        $data = 'Hello, World!';
        $invalidKey = 'shortkey';

        $encryptedData = Crypt::encrypt($data, $invalidKey);
        $this->assertSame($data, $encryptedData, 'Data should not be encrypted with invalid key.');
    }

    public function testDecryptWithInvalidKey()
    {
        $data = 'Hello, World!';
        $encryptedData = Crypt::encrypt($data, $this->key);
        $invalidKey = 'shortkey';

        $decryptedData = Crypt::decrypt($encryptedData, $invalidKey);
        $this->assertNotSame($data, $decryptedData, 'Data should not be decrypted with invalid key.');
    }

    public function testIsEncrypted()
    {
        $encryptedData = Crypt::encrypt('Hello, World!', $this->key);
        $this->assertTrue(Crypt::isEncrypted($encryptedData), 'Data should be identified as encrypted.');

        $this->assertFalse(Crypt::isEncrypted('Not encrypted data'), 'Plain data should not be identified as encrypted.');
        $this->assertFalse(Crypt::isEncrypted(12345), 'Numeric data should not be identified as encrypted.');
        $this->assertFalse(Crypt::isEncrypted([]), 'Array should not be identified as encrypted.');
        $this->assertFalse(Crypt::isEncrypted(new \stdClass()), 'Object should not be identified as encrypted.');
    }

    public function testValidateSodiumEnabled()
    {
        // This test is tricky since sodium is already enabled if tests are running
        // We will skip this test because it's hard to mock extension_loaded function in PHP
        $this->markTestSkipped('Cannot test validateSodiumEnabled method as sodium extension is already loaded.');
    }

    public function testEncryptWithSodiumDisabled()
    {
        if (extension_loaded('sodium')) {
            $this->markTestSkipped('The sodium extension is loaded, cannot test without it.');
        }

        $this->expectException(AiraloException::class);
        $this->expectExceptionMessage('Sodium library is not loaded');

        Crypt::encrypt('Hello, World!', $this->key);
    }

    public function testDecryptWithSodiumDisabled()
    {
        if (extension_loaded('sodium')) {
            $this->markTestSkipped('The sodium extension is loaded, cannot test without it.');
        }

        $this->expectException(AiraloException::class);
        $this->expectExceptionMessage('Sodium library is not loaded');

        Crypt::decrypt('Hello, World!', $this->key);
    }
}