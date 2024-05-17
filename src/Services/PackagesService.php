<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Helpers\Cached;
use Airalo\Helpers\EasyAccess;
use Airalo\Resources\CurlResource;
use Airalo\Responses\PackagesResponse;

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
    }

    /**
     * @param array $params
     * @return EasyAccess|null
     */
    public function getPackages(array $params = [])
    {
        $url = $this->buildUrl($params);

        $result = Cached::get(function () use ($url, $params) {
            $currentPage = $params['page'] ?? 0;
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
                    return null;
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

            if ($params['object']) {
                return new PackagesResponse($result);
            }

            return new EasyAccess($params['flat'] ? $this->flatten($result) : $result);
        }, $this->getKey($url, $params), 3600);

        if ($result instanceof PackagesResponse) {
            return $result->getItemsCount() ? $result : null;
        }

        return count($result['data']) ? $result : null;
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

    /**
     * @param array $data
     * @return array
     */
    private function flatten(array $data): array
    {
        $flattened = ['data' => []];

        foreach ($data['data'] as $each) {
            foreach ($each['operators'] as $operator) {
                foreach ($operator['packages'] as $package) {
                    $countries = [];

                    foreach ($operator['countries'] as $country) {
                        $countries[] = $country['country_code'];
                    }

                    $flattened['data'][] = [
                        'package_id' => $package['id'],
                        'slug' => $each['slug'],
                        'type' => $package['type'],
                        'price' => $package['price'],
                        'net_price' => $package['net_price'],
                        'amount' => $package['amount'],
                        'day' => $package['day'],
                        'is_unlimited' => $package['is_unlimited'],
                        'title' => $package['title'],
                        'data' => $package['data'],
                        'short_info' => $package['short_info'],
                        'voice' => $package['voice'],
                        'text' => $package['text'],
                        'plan_type' => $operator['plan_type'],
                        'activation_policy' => $operator['activation_policy'],
                        'operator' => [
                            'title' => $operator['title'],
                            'is_roaming' => $operator['is_roaming'],
                            'info' => $operator['info'],
                        ],
                        'countries' => $countries,
                    ];
                }
            }
        }

        return $flattened;
    }

    /**
     * @param string $url
     * @param array $params
     * @return string
     */
    private function getKey(string $url, array $params): string
    {
        return md5($url . json_encode($params) . json_encode($this->config->getHttpHeaders()));
    }
}
