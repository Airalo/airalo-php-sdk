<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Constants\SdkConstants;
use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\EasyAccess;
use Airalo\Helpers\Signature;
use Airalo\Resources\CurlResource;
use Airalo\Resources\MultiCurlResource;

class VoucherService
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
        if (!$accessToken) {
            throw new AiraloException('Invalid access token please check your credentials');
        }

        $this->config = $config;
        $this->curl = $curl;
        $this->multiCurl = $multiCurl;
        $this->signature = $signature;
        $this->accessToken = $accessToken;
    }


    public function createVoucher(array $payload): ?EasyAccess
    {

        $this->validateVoucher($payload);

        $response = $this->curl
            ->setHeaders($this->getHeaders($payload))
            ->post($this->config->getUrl() . ApiConstants::VOUCHERS_SLUG, $payload);

        if ($this->curl->code != 200) {
            throw new AiraloException(
                'Voucher creation failed, status code: ' . $this->curl->code . ', response: ' . $response
            );
        }

        return new EasyAccess($response);
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

    /**
     * @throws AiraloException
     * @param array $payload
     * @return void
     */
    private function validateVoucher(array $payload): void
    {
        if (!isset($payload['amount']) || $payload['amount'] == '' || $payload['amount'] < 1) {
            throw new AiraloException('The amount is required, payload: ' . json_encode($payload));
        }

        if ($payload['amount'] > SdkConstants::VOUCHER_MAX_NUM) {
            throw new AiraloException('The amount may not be greater than ' . SdkConstants::VOUCHER_MAX_NUM);
        }

        if (isset($payload['voucher_code']) && strlen($payload['voucher_code']) > 255) {
            throw new AiraloException('The voucher_code may not increase 255 characters ');
        }

        if (isset($payload['usage_limit']) && ($payload['usage_limit'] < 1 || $payload['usage_limit'] > SdkConstants::VOUCHER_MAX_NUM)) {
            throw new AiraloException('The usage_limit may not be greater than ' . SdkConstants::VOUCHER_MAX_NUM);
        }

        if (!isset($payload['quantity']) || $payload['quantity'] == '' || $payload['quantity'] < 1) {
            throw new AiraloException('The quantity is required, payload: ' . json_encode($payload));
        }

        if ($payload['quantity'] > SdkConstants::VOUCHER_MAX_QUANTITY) {
            throw new AiraloException('The quantity may not be greater than ' . SdkConstants::VOUCHER_MAX_NUM);
        }

    }

}