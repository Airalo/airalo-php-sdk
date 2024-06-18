<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\Cached;
use Airalo\Helpers\EasyAccess;
use Airalo\Resources\CurlResource;

class InstallationInstructionsService
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
     * @param array<string, mixed> $params An associative array of parameters
     * @return EasyAccess|null
     */
    public function getInstructions(array $params = []): ?EasyAccess
    {
        $url = $this->buildUrl($params);

        $result = Cached::get(function () use ($url, $params) {

            /* @phpstan-ignore-next-line */
            $response = $this->curl->setHeaders([
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken,
                'Accept-Language: ' . $params['language']
            ])->get($url);


            $result = json_decode($response, true);

            return new EasyAccess($result);
        }, $this->getKey($url, $params), 3600);

        /* @phpstan-ignore-next-line */
        return count($result['data']) ? $result : null;
    }

    /**
     * Builds a URL based on the provided parameters.
     *
     * @param array<string, mixed> $params An associative array of parameters. Must include the 'iccid' key.
     * @return string The constructed URL.
     * @throws AiraloException if the 'iccid' parameter is not provided or is not a valid type.
     */
    private function buildUrl(array $params): string
    {

        if (!isset($params['iccid'])) {
            throw new AiraloException('The parameter "iccid" is required.');
        }

        /* @phpstan-ignore-next-line */
        $iccid = (string) $params['iccid'];
        $url = sprintf(
            '%s%s/%s/%s',
            $this->baseUrl,
            ApiConstants::SIMS_SLUG,
            $iccid,
            ApiConstants::INSTRUCTIONS_SLUG
        );

        return $url;
    }

    /**
     * Generates a unique key based on the provided URL, parameters, HTTP headers, and access token.
     *
     * @param string $url The base URL.
     * @param array<string, mixed> $params An associative array of parameters.
     * @return string The generated unique key.
     */
    private function getKey(string $url, array $params): string
    {
        return md5($url . json_encode($params) . json_encode($this->config->getHttpHeaders())  . $this->accessToken);
    }
}
