<?php

namespace Airalo;

use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;

final class Config
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

        if ($jsonError !== JSON_ERROR_NONE) {
            throw new AiraloException("Invalid config data provided, error code: $jsonError");
        }

        $this->validate();
    }

    /**
     * @param string $key
     * @param mixed $value
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
     * @return array
     */
    public function getCredentials(): array
    {
        return [
            'client_id' => $this->data['client_id'],
            'client_secret' => $this->data['client_secret'],
        ];
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
        return $this->data['env'] == 'sandbox'
            ? ApiConstants::SANBOX_URL
            : ApiConstants::PRODUCTION_URL;
    }

    /**
     * @return void
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

        if (!in_array($this->data['env'], self::ENVIRONMENTS)) {
            throw new AiraloException(
                "Invalid environment provided: `{$this->data['env']}`, allowed: " . implode(', ', self::ENVIRONMENTS)
            );
        }
    }
}
