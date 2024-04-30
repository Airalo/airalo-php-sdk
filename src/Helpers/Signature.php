<?php

namespace Airalo\Helpers;

class Signature
{
    private string $secret;

    /**
     * @param string $secret
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param mixed $payload
     * @return string|null
     */
    public function getSignature($payload): ?string
    {
        if (!$payload = $this->preparePayload($payload)) {
            return null;
        }

        return $this->signData($payload);
    }

    /**
     * @param string|null $hash
     * @param mixed $payload
     * @return boolean
     */
    public function checkSignature(?string $hash = null, $payload = null): bool
    {
        if (!$hash || !$payload = $this->preparePayload($payload)) {
            return false;
        }

        return hash_equals($this->signData($payload), $hash);
    }

    /**
     * @param mixed $payload
     * @return string|null
     */
    private function preparePayload($payload): ?string
    {
        if (!$payload) {
            return null;
        }

        if (is_string($payload)) {
            // remove all whitespaces
            $payload = json_encode(json_decode($payload, true));
        }

        if (!is_string($payload)) {
            $payload = json_encode($payload);
        }

        return $payload;
    }

    /**
     * @param string $payload
     * @param string $algo
     * @return string
     */
    private function signData(string $payload, string $algo = 'sha512'): string
    {
        return hash_hmac($algo, $payload, $this->secret);
    }
}
