<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\Cached;
use Airalo\Helpers\Date;
use Airalo\Helpers\EasyAccess;
use Airalo\Resources\CurlResource;

class ExchangeRatesService
{
    private Config $config;

    private CurlResource $curl;
    private string $baseUrl;
    private string $accessToken;

    /**
     * @param Config $config
     * @param CurlResource $curl
     * @param string $accessToken
     * @throws AiraloException
     */
    public function __construct(
        Config       $config,
        CurlResource $curl,
        string       $accessToken
    )
    {
        if (!$accessToken) {
            throw new AiraloException('Invalid access token please check your credentials');
        }

        $this->config = $config;
        $this->curl = $curl;
        $this->accessToken = $accessToken;
        $this->baseUrl = $this->config->getUrl();
    }

    /**
     * @param array<string, string|null> $params
     * @return EasyAccess|null
     * @throws AiraloException
     */
    public function exchangeRates(array $params = []): ?EasyAccess
    {
        $this->validateExchangeRatesRequest($params);
        $url = $this->buildUrl($params);

        $result = Cached::get(function () use ($url) {
            /* @phpstan-ignore-next-line */
            $response = $this->curl->setHeaders([
                'Accept: application/json',
                'Authorization: Bearer ' . $this->accessToken,
            ])->get($url);

            $result = json_decode($response, true);
            return new EasyAccess($result);
        }, $this->getKey($url, $params), 300);

        /* @phpstan-ignore-next-line */
        return count($result['data']) ? $result : null;
    }

    /**
     * @param array<string, string|null> $params
     * @return void
     * @throws AiraloException
     */
    private function validateExchangeRatesRequest(array $params): void
    {
        if (!empty($params['date'])) {
            if (!Date::validateDate($params['date'])) {
                throw new AiraloException('Please enter a valid date in the format YYYY-MM-DD');
            }
        }

        if (!empty($params['to'])) {
            if (!preg_match('/^([A-Za-z]{3})(?:,([A-Za-z]{3}))*$/', $params['to'])) {
                throw new AiraloException('Please enter a comma separated list of currency codes. Each code must have 3 letters');
            }
        }
    }

    /**
     * Build the URL for the exchange rates API.
     *
     * @param array<string, mixed> $params
     * @return string
     */
    private function buildUrl(array $params): string
    {
        $url = $this->baseUrl . ApiConstants::EXCHANGE_RATES_SLUG . '?';
        $queryParams = [];

        if (isset($params['date'])) {
            $queryParams['date'] = $params['date'];
        }

        if (isset($params['to'])) {
            $queryParams['to'] = $params['to'];
        }

        return $url . http_build_query($queryParams);
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
        return md5($url . json_encode($params) . json_encode($this->config->getHttpHeaders()) . $this->accessToken);
    }
}
