<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;
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
        if (!$accessToken) {
            throw new AiraloException('Invalid access token please check your credentials');
        }

        $this->accessToken = $accessToken;

        $this->config = $config;
        $this->baseUrl = $this->config->getUrl();

        $this->curl = $curl;
    }

    /**
     * @param array $params
     * @return EasyAccess|null
     */
    public function getPackages(array $params = [], string $locale = 'en'): ?EasyAccess
    {
        $url = $this->buildUrl($params);
        $cacheParams = array_merge($params, ['locale' => $locale]);
        $result = Cached::get(function () use ($url, $params, $locale) {
            $currentPage = $params['page'] ?? 1;
            $result = ['data' => []];

            while (true) {
                if ($currentPage) {
                    $pageUrl = $url . "&page=$currentPage";
                }

                $response = $this->curl->setHeaders([
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->accessToken,
                    'Accept-Language: ' . $locale,
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

            return new EasyAccess($params['flat'] ? $this->flatten($result) : $result);
        }, $this->getKey($url, $cacheParams), 3600);

        return count($result['data']) ? $result : null;
    }

    /**
     * @param array $params
     * @return string
     */
    private function buildUrl(array $params): string
    {
        $url = $this->baseUrl . ApiConstants::PACKAGES_SLUG . '?';

        $queryParams = [];
        $queryParams['include'] = 'topup';

        if (isset($params['simOnly']) && $params['simOnly'] === true) {
            unset($queryParams['include']);
        }

        if (isset($params['type']) && $params['type'] == 'local') {
            $queryParams['filter[type]'] = 'local';
        }
        if (isset($params['type']) && $params['type'] == 'global') {
            $queryParams['filter[type]'] = 'global';
        }
        if (isset($params['country'])) {
            $queryParams['filter[country]'] = $params['country'];
        }
        if (isset($params['limit']) && $params['limit'] > 0) {
            $queryParams['limit'] = $params['limit'];
        }

        return $url . http_build_query($queryParams);
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
                        'image' => $operator['image']['url'] ?? null,
                        'other_info' => $operator['other_info'],
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
        return md5($url . json_encode($params) . json_encode($this->config->getHttpHeaders()) . $this->accessToken);
    }
}
