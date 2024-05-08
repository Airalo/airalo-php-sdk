<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Helpers\Cached;
use Airalo\Helpers\EasyAccess;
use Airalo\Resources\CurlResource;

class PackagesService
{
    private string $accessToken;

    private string $baseUrl;

    private Config $config;

    private CurlResource $curl;

    /**
     * @param Config $config
     * @param CurlResource $curl
     * @param string $accessToken
     */
    public function __construct(Config $config, CurlResource $curl, string $accessToken)
    {
        $this->accessToken = $accessToken;

        $this->config = $config;
        $this->baseUrl = $this->config->getUrl();

        $this->curl = $curl;
        $this->curl->setHeaders([
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken,
        ]);
    }

    /**
     * @param array $params
     * @return EasyAccess|null
     */
    public function getPackages(array $params = []): ?EasyAccess
    {
        $url = $this->buildUrl($params);

        return Cached::get(function () use ($url, $params) {
            $currentPage = $params['page'] ?? 0;
            $result = ['data' => []];

            while (true) {
                if ($currentPage) {
                    $pageUrl = $url . "&page=$currentPage";
                }

                if (!$response = $this->curl->get($pageUrl ?? $url)) {
                    return null;
                }

                $response = json_decode($response, true);

                if (empty($response['data'])) {
                    return null;
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
        }, $url, 3600);
    }

    /**
     * @param array $params
     * @return string
     */
    private function buildUrl(array $params): string
    {
        $url = $this->baseUrl . ApiConstants::PACKAGES_SLUG . '?include=topup';

        if (isset($params['type']) && $params['type'] == 'local') {
            $url .= '&filter[type]=local';
        }
        if (isset($params['type']) && $params['type'] == 'global') {
            $url .= '&filter[type]=global';
        }
        if (isset($params['country'])) {
            $url .= '&filter[country]=' . $params['country'];
        }
        if (isset($params['limit']) && $params['limit'] > 0) {
            $url .= '&limit=' . $params['limit'];
        }

        return $url;
    }
}
