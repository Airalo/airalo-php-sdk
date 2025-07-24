<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\EasyAccess;
use Airalo\Resources\CurlResource;

class CompatibilityDevicesService
{
    private Config $config;

    private CurlResource $curl;

    private string $baseUrl;

    private string $accessToken;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param string $accessToken
     * @throws AiraloException
     */
    public function __construct(
        Config $config,
        CurlResource $curl,
        string $accessToken
    ) {
        if (!$accessToken) {
            throw new AiraloException('Invalid access token please check your credentials');
        }

        $this->config = $config;
        $this->curl = $curl;
        $this->accessToken = $accessToken;
        $this->baseUrl = $this->config->getUrl();
    }

    /**
     * @return EasyAccess|null
     */
    public function getCompatibleDevices(): ?EasyAccess
    {
        $url = $this->buildUrl();

        /* @phpstan-ignore-next-line */
        $response = $this->curl->setHeaders([
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken,
        ])->get($url);

        /* @phpstan-ignore-next-line */
        $result = json_decode($response, true);

        /* @phpstan-ignore-next-line */
        return count($result['data']) ? new EasyAccess($result) : null;
    }

    /**
     * Builds a URL based on the provided parameters.
     *
     * @return string The constructed URL.
     * @throws AiraloException if the 'iccid' parameter is not provided or is not a valid type.
     */
    private function buildUrl(): string
    {
        $url = sprintf(
            '%s%s',
            $this->baseUrl,
            ApiConstants::COMPATIBILITY_SLUG
        );

        return $url;
    }
}
