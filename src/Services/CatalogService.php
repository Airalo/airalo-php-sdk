<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\Cached;
use Airalo\Helpers\EasyAccess;
use Airalo\Resources\CurlResource;

class CatalogService
{
    private Config $config;

    private CurlResource $curl;
    private string $baseUrl;
    private string $accessToken;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param string $accessToken
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

    public function catalogsBulk($params)
    {
        $url = $this->buildUrl();

       $result = Cached::get(function () use ($url) {
            $currentPage = $params['page'] ?? 1;
            $result = ['data' => []];

            while (true) {
                if ($currentPage) {
                    $pageUrl = $url . "&page=$currentPage";
                }

                $response = $this->curl->setHeaders([
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->accessToken,
                ])->get($pageUrl ?? $url);

                if (!$response) {
                    return $result;
                }

                $response = json_decode($response, true);

                if (empty($response['data'])) {
                    break;
                }

                $result['data'] = array_merge($result['data'], $response['data']);

                if (isset($params['limit']) && count($result['data']) >= $params['limit']) {
                    break;
                }

                if ($response['meta']['last_page'] == $currentPage) {
                    break;
                }

                $currentPage++;
            }

            return new EasyAccess($result);
        }, $this->getKey($url, $params), 3600);

        return count($result['data']) ? $result : null;
    }

    private function getKey(string $url, array $params): string
    {
        return md5($url . json_encode($params) . json_encode($this->config->getHttpHeaders())  . $this->accessToken);
    }

    private function buildUrl(): string
    {
        return $this->baseUrl . ApiConstants::OVERRIDE_SLUG . '?';
    }
}