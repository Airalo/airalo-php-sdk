<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Constants\SdkConstants;
use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\EasyAccess;
use Airalo\Helpers\Signature;
use Airalo\Resources\CurlResource;

class VoucherService
{

    private Config $config;

    private CurlResource $curl;

    private Signature $signature;

    private string $accessToken;


    /**
     * @param Config $config
     * @param CurlResource $curl
     * @param Signature $signature
     * @param string $accessToken
     * @throws AiraloException
     */
    public function __construct(
        Config $config,
        CurlResource $curl,
        Signature $signature,
        string $accessToken
    ) {
        if (!$accessToken) {
            throw new AiraloException('Invalid access token please check your credentials');
        }

        $this->config = $config;
        $this->curl = $curl;
        $this->signature = $signature;
        $this->accessToken = $accessToken;
    }


    /**
     * @param array<string, mixed> $payload Associative array of payload data
     * @return EasyAccess|null
     * @throws AiraloException
     */
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
     * @param array<string, mixed> $payload Associative array of payload data
     * @return EasyAccess|null
     * @throws AiraloException
     */
    public function createEsimVoucher(array $payload): ?EasyAccess
    {

        $this->validateEsimVoucher($payload);

        $response = $this->curl
            ->setHeaders($this->getHeaders($payload))
            ->post($this->config->getUrl() . ApiConstants::VOUCHERS_ESIM_SLUG, $payload);

        if ($this->curl->code != 200) {
            throw new AiraloException(
                'Voucher creation failed, status code: ' . $this->curl->code . ', response: ' . $response
            );
        }

        return new EasyAccess($response);
    }



    /**
     * @param array<string, mixed> $payload Associative array of payload data
     * @return array<string> List of header strings
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
     * Validate the voucher payload.
     *
     * @param array<string, mixed> $payload Associative array of payload data
     * @throws AiraloException
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

        if (isset($payload['voucher_code']) && is_string($payload['voucher_code']) && strlen($payload['voucher_code']) > 255) {
            throw new AiraloException('The voucher code may not exceed 255 characters.');
        }

        if (isset($payload['voucher_code']) && isset($payload['quantity']) && $payload['quantity'] > 1) {
            throw new AiraloException('The selected voucher code allows a maximum quantity of 1');
        }

        if (isset($payload['usage_limit']) && ($payload['usage_limit'] < 1 || $payload['usage_limit'] > SdkConstants::VOUCHER_MAX_NUM)) {
            throw new AiraloException('The usage_limit may not be greater than ' . SdkConstants::VOUCHER_MAX_NUM);
        }

        if (!isset($payload['quantity']) || $payload['quantity'] == '' || $payload['quantity'] < 1) {
            throw new AiraloException('The quantity is required, payload: ' . json_encode($payload));
        }

        if ($payload['quantity'] > SdkConstants::VOUCHER_MAX_QUANTITY) {
            throw new AiraloException('The quantity may not be greater than ' . SdkConstants::VOUCHER_MAX_QUANTITY);
        }

    }

    /**
     * Validate the esim voucher payload.
     *
     * @param array<string, mixed> $payload Associative array of payload data
     * @throws AiraloException
     * @return void
     */
    private function validateEsimVoucher(array $payload): void
    {
        if (empty($payload['vouchers'])) {
            throw new AiraloException('vouchers field is required, payload: ' . json_encode($payload));
        }

        if (!is_array($payload['vouchers'])) {
            throw new AiraloException('vouchers field should be an array, payload: ' . json_encode($payload));
        }

        foreach ($payload['vouchers'] as $voucher) {
            if (empty($voucher['package_id'])) {
                throw new AiraloException('The vouchers.package_id is required, payload: ' . json_encode($payload));
            }

            if (empty($voucher['quantity'])) {
                throw new AiraloException('The vouchers.quantity is required and should be greater than 0, payload: ' . json_encode($payload));
            }

            if ($payload['quantity'] > SdkConstants::VOUCHER_MAX_QUANTITY) {
                throw new AiraloException('The vouchers.quantity may not be greater than ' . SdkConstants::VOUCHER_MAX_QUANTITY);
            }
        }
    }

}