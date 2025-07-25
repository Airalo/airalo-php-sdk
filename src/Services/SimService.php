<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\EasyAccess;
use Airalo\Resources\CurlResource;
use Airalo\Helpers\Cached;
use Airalo\Resources\MultiCurlResource;

class SimService
{
    private Config $config;

    private CurlResource $curl;
    private MultiCurlResource $multiCurl;
    private string $baseUrl;
    private string $accessToken;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param MultiCurlResource $multiCurl
     * @param string $accessToken
     */
    public function __construct(
        Config $config,
        CurlResource $curl,
        MultiCurlResource $multiCurl,
        string $accessToken
    ) {
        if (!$accessToken) {
            throw new AiraloException('Invalid access token please check your credentials');
        }

        $this->config = $config;
        $this->curl = $curl;
        $this->multiCurl = $multiCurl;
        $this->accessToken = $accessToken;
        $this->baseUrl = $this->config->getUrl();
    }

    /**
     * @param array<string, mixed> $params An associative array of parameters
     * @return EasyAccess|null
     */
    public function simUsage(array $params = []): ?EasyAccess
    {
        $url = $this->buildUrl($params);

        $result = Cached::get(function () use ($url) {

            /* @phpstan-ignore-next-line */
            $response = $this->curl->setHeaders([
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken,
            ])->get($url);

            $result = json_decode($response, true);

            return new EasyAccess($result);
        }, $this->getKey($url, $params), 300);

        /* @phpstan-ignore-next-line */
        return count($result['data']) ? $result : null;
    }

    /**
     * @param array<string> $iccids
     * @return mixed
     */
    public function simUsageBulk(array $iccids = [])
    {
        foreach ($iccids as $iccid) {
            $this->multiCurl
                ->tag($iccid)
                ->setHeaders([
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->accessToken,
                ])->get($this->buildUrl(['iccid' => $iccid]));
        }

        return Cached::get(function () {
            if (!$response = $this->multiCurl->exec()) {
                return null;
            }

            $result = [];
            foreach ($response as $iccid => $each) {
                $result[$iccid] = new EasyAccess($each);
            }

            return new EasyAccess($result);
        }, $this->getKey(implode('', $iccids), []), 300);
    }

    /**
     * @param array<string, mixed> $params An associative array of parameters
     * @return EasyAccess|null
     */
    public function simTopups(array $params = []): ?EasyAccess
    {
        $url = $this->buildUrl($params, ApiConstants::SIMS_TOPUPS);

        $result = Cached::get(function () use ($url) {

            /* @phpstan-ignore-next-line */
            $response = $this->curl->setHeaders([
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken,
            ])->get($url);

            $result = json_decode($response, true);

            return new EasyAccess($result);
        }, $this->getKey($url, $params), 300);

        /* @phpstan-ignore-next-line */
        return count($result['data']) ? $result : null;
    }

    /**
     * @param array<string, mixed> $params An associative array of parameters
     * @return EasyAccess|null
     */
    public function simPackageHistory(array $params = []): ?EasyAccess
    {
        $url = $this->buildUrl($params, ApiConstants::SIMS_PACKAGES);

        $result = Cached::get(function () use ($url) {
            /* @phpstan-ignore-next-line */
            $response = $this->curl->setHeaders([
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken,
            ])->get($url);

            $result = json_decode($response, true);

            return new EasyAccess($result);
        }, $this->getKey($url, $params), 900);

        /* @phpstan-ignore-next-line */
        return count($result['data']) ? $result : null;
    }

    /**
     * Builds a URL based on the provided parameters.
     *
     * @param array<string, mixed> $params An associative array of parameters. Must include the 'iccid' key.
     * @param string|null $slug The slug to append to the base URL.
     * @return string The constructed URL.
     * @throws AiraloException if the 'iccid' parameter is not provided or is not a valid type.
     */
    private function buildUrl(array $params, ?string $slug = null): string
    {
        if (!isset($params['iccid']) || !$this->isIccid($params['iccid'])) {
            throw new AiraloException('The parameter "iccid" is invalid.');
        }

        /* @phpstan-ignore-next-line */
        $iccid = (string) $params['iccid'];

        return sprintf(
            '%s%s/%s/%s',
            $this->baseUrl,
            ApiConstants::SIMS_SLUG,
            $iccid,
            $slug ?? ApiConstants::SIMS_USAGE
        );
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

    /**
     * @param mixed $val
     * @return boolean
     */
    private function isIccid($val): bool
    {
        if ($this->config->getEnvironment() !== 'production') {
            return is_numeric($val)
                && strlen($val) <= 22;
        }

        return is_numeric($val)
            && strlen($val) >= 18
            && strlen($val) <= 22;
    }
}
