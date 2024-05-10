<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\EasyAccess;
use Airalo\Resources\CurlResource;
use Airalo\Helpers\Signature;

class TopupService
{
    private Config $config;

    private CurlResource $curl;

    private Signature $signature;

    private string $accessToken;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param Signature $signature
     * @param string $accessToken
     */
    public function __construct(
        Config $config,
        CurlResource $curl,
        Signature $signature,
        string $accessToken
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->signature = $signature;
        $this->accessToken = $accessToken;
    }

    /**
     * @param array $payload
     * @return EasyAccess|null
     */
    public function createTopup(array $payload): ?EasyAccess
    {
        $this->validateTopup($payload);

        $response = $this->curl
            ->setHeaders([
                'Accept: application/json',
                'Authorization: Bearer ' . $this->accessToken,
                'airalo-signature: ' . $this->signature->getSignature($payload),
            ])
            ->post($this->config->getUrl() . ApiConstants::TOPUPS_SLUG, http_build_query($payload));

        if ($this->curl->code != 200) {
            throw new AiraloException(
                'Topup creation failed, status code: ' . $this->curl->code . ', response: ' . $response
            );
        }

        return new EasyAccess($response);
    }

    /**
     * @throws AiraloException
     * @param array $payload
     * @return void
     */
    private function validateTopup(array $payload): void
    {
        if (!isset($payload['package_id']) || $payload['package_id'] == '') {
            throw new AiraloException('The package_id is required, payload: ' . json_encode($payload));
        }

        if (!isset($payload['iccid']) || $payload['iccid'] == '') {
            throw new AiraloException('The iccid is required, payload: ' . json_encode($payload));
        }
    }
}
