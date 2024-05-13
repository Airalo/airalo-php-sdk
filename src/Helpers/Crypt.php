<?php

namespace Airalo\Helpers;

use Airalo\Exceptions\AiraloException;

final class Crypt
{
    /**
     * @param string $data
     * @param string $key
     * @return string
     */
    public static function encrypt(string $data, string $key): string
    {
        self::validateSodiumEnabled();

        $key = substr($key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

        if (!$key || strlen($key) != SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $data;
        }

        if (self::isEncrypted($data)) {
            return $data;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $encrypted = sodium_crypto_secretbox($data, $nonce, $key);

        return base64_encode($nonce . $encrypted);
    }

    /**
     * @param string $data
     * @param string $key
     * @return string
     */
    public static function decrypt(string $data, string $key): string
    {
        self::validateSodiumEnabled();

        $key = substr($key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

        if (!$key || strlen($key) != SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $data;
        }

        if (!self::isEncrypted($data)) {
            return $data;
        }

        $encrypted = base64_decode($data);

        $nonce = substr($encrypted, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $encrypted = substr($encrypted, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return sodium_crypto_secretbox_open($encrypted, $nonce, $key);
    }

    /**
     * @param mixed $data
     * @return bool
     */
    public static function isEncrypted($data): bool
    {
        if (is_array($data) || is_object($data)) {
            return false;
        }

        if (strlen($data) < 56) {
            return false;
        }

        if (!$base64Decoded = base64_decode($data, true)) {
            return false;
        }

        return !is_numeric($data) && base64_encode($base64Decoded) === $data;
    }

    /**
     * @throws AiraloException
     * @return void
     */
    private static function validateSodiumEnabled(): void
    {
        if (!extension_loaded('sodium')) {
            throw new AiraloException('Sodium library is not loaded');
        }
    }
}
