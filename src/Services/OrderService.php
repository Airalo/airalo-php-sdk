<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\EasyAccess;
use Airalo\Resources\CurlResource;
use Airalo\Helpers\Signature;
use Airalo\Resources\MultiCurlResource;

class OrderService
{
    private Config $config;

    private CurlResource $curl;

    private MultiCurlResource $multiCurl;

    private Signature $signature;

    private string $accessToken;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param MultiCurlResource $multiCurl
     * @param Signature $signature
     * @param string $accessToken
     */
    public function __construct(
        Config $config,
        CurlResource $curl,
        MultiCurlResource $multiCurl,
        Signature $signature,
        string $accessToken
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->multiCurl = $multiCurl;
        $this->signature = $signature;
        $this->accessToken = $accessToken;
    }

    /**
     * @param array $payload
     * @return EasyAccess|null
     */
    public function createOrder(array $payload): ?EasyAccess
    {
        $response = $this->curl
            ->setHeaders($this->getHeaders($payload))
            ->post($this->config->getUrl() . ApiConstants::ORDERS_SLUG, $payload);

        if ($this->curl->code != 200) {
            throw new AiraloException(
                'Order creation failed, status code: ' . $this->curl->code . ', response: ' . $response
            );
        }

        return new EasyAccess($response);
    }

    /**
     * @param array $params
     * @param string|null $description
     * @return EasyAccess|null
     */
    public function createOrderBulk(array $params, ?string $description = null): ?EasyAccess
    {
        foreach ($params as $packageId => $quantity) {
            $payload = [
                'package_id' => $packageId,
                'quantity' => $quantity,
                'type' => 'sim',
                'description' => $description ?? 'Bulk order placed via Airalo PHP SDK',
            ];

            $this->multiCurl
                ->tag($packageId)
                ->setHeaders($this->getHeaders($payload))
                ->post($this->config->getUrl() . ApiConstants::ORDERS_SLUG, $payload);
        }

        if (!$response = $this->multiCurl->exec()) {
            return null;
        }

        $result = [];

        foreach ($response as $key => $response) {
            $result[$key] = new EasyAccess($response);
        }

        return new EasyAccess($result);
    }

    /**
     * @param array $payload
     * @return array
     */
    private function getHeaders(array $payload): array
    {
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken,
            'airalo-signature: ' . $this->signature->getSignature($payload),
        ];
    }
}
