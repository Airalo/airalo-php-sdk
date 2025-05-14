<?php

namespace Airalo;

use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;

class Config
{
    private const MANDATORY_CONFIG_KEYS = [
        'client_id',
        'client_secret',
    ];

    private const ENVIRONMENTS = [
        'sandbox',
        'production',
    ];

    private array $data = [];

    /**
     * @param mixed $data
     * @throws AiraloException
     */
    public function __construct($data)
    {
        if (!$data) {
            throw new AiraloException('Config data is not provided');
        }

        $this->data = !is_array($data)
            ? json_decode(json_encode($data), true)
            : $data;

        $jsonError = json_last_error();

        if ($jsonError !== JSON_ERROR_NONE && empty($this->data)) {
            throw new AiraloException("Invalid config data provided, error code: $jsonError");
        }

        $this->validate();
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->data;
    }

    /**
     * @param bool $asString
     * @return array|string
     */
    public function getCredentials(bool $asString = false)
    {
        $credentials = [
            'client_id' => $this->data['client_id'],
            'client_secret' => $this->data['client_secret'],
        ];

        if ($asString) {
            return http_build_query($credentials);
        }

        return $credentials;
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->data['env'];
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->data['api_url'] ?? 'https://partners-api.airalo.com/v2/';
    }

    /**
     * @return array
     */
    public function getHttpHeaders(): array
    {
        return $this->data['http_headers'] ?? [];
    }

    /**
     * @return void
     * @throws AiraloException
     */
    private function validate(): void
    {
        $configKeys = array_keys($this->data);

        foreach (self::MANDATORY_CONFIG_KEYS as $key) {
            if (
                !in_array($key, $configKeys)
                || !isset($this->data[$key])
                || !$this->data[$key]
            ) {
                throw new AiraloException("Mandatory field `$key` is missing in the provided config data");
            }
        }

        if (!isset($this->data['env'])) {
            $this->data['env'] = 'production';
        }
    }
}
