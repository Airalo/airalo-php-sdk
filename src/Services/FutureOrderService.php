<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Helpers\Signature;
use Airalo\Helpers\EasyAccess;
use Airalo\Constants\ApiConstants;
use Airalo\Constants\SdkConstants;
use Airalo\Resources\CurlResource;
use Airalo\Exceptions\AiraloException;

class FutureOrderService
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
     * @param array $payload
     * @return EasyAccess|null
     */
    public function createFutureOrder(array $payload): ?EasyAccess
    {
        $this->validateFutureOrder($payload);

        $payload = array_filter($payload);

        $response = $this->curl
            ->setHeaders($this->getHeaders($payload))
            ->post($this->config->getUrl() . ApiConstants::FUTURE_ORDERS, $payload);

        if ($this->curl->code != 200) {
            throw new AiraloException(
                'Future order creation failed, status code: ' . $this->curl->code . ', response: ' . $response
            );
        }

        return new EasyAccess($response);
    }

    /**
     * @param array $payload
     * @return EasyAccess|null
     */
    public function cancelFutureOrder(array $payload): ?EasyAccess
    {
        $this->validateCancelFutureOrder($payload);

        $response = $this->curl
            ->setHeaders($this->getHeaders($payload))
            ->post($this->config->getUrl() . ApiConstants::CANCEL_FUTURE_ORDERS, $payload);

        if ($this->curl->code != 200) {
            throw new AiraloException(
                'Future order cancellation failed, status code: ' . $this->curl->code . ', response: ' . $response
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
    private function validateFutureOrder(array $payload): void
    {
        if (!isset($payload['package_id']) || $payload['package_id'] == '') {
            throw new AiraloException('The package_id is required, payload: ' . json_encode($payload));
        }

        if ($payload['quantity'] < 1) {
            throw new AiraloException('The quantity is required, payload: ' . json_encode($payload));
        }

        if ($payload['quantity'] > SdkConstants::FUTURE_ORDER_LIMIT) {
            throw new AiraloException('The packages count may not be greater than ' . SdkConstants::BULK_ORDER_LIMIT);
        }

        if (!isset($payload['due_date']) || $payload['due_date'] == '') {
            throw new AiraloException('The due_date is required (format: Y-m-d H:i), payload: ' . json_encode($payload));
        }

        $date = \DateTime::createFromFormat('Y-m-d H:i', $payload['due_date']);
        if (!$date || $date->format('Y-m-d H:i') !== $payload['due_date']) {
            throw new AiraloException('The due_date must be in the format Y-m-d H:i, payload: ' . json_encode($payload));
        }

        $emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

        if (isset($payload['to_email']) && !preg_match($emailRegex, $payload['to_email'])) {
            throw new AiraloException('The to_email: ' . $payload['to_email'] . ' must be valid email address, payload: ' . json_encode($payload));
        }

        if (isset($payload['sharing_option'])) {
            foreach ($payload['sharing_option'] as $sharingOption) {
                if (!in_array($sharingOption, ['link', 'pdf'])) {
                    throw new AiraloException('The sharing_option may be link or pdf or both, payload: ' . json_encode($payload));
                }
            }
        }

        if (isset($payload['copy_address'])) {
            foreach ($payload['copy_address'] as $eachCCemail) {
                if (!preg_match($emailRegex, $eachCCemail)) {
                    throw new AiraloException("The copy_address: $eachCCemail must be valid email address, payload: " . json_encode($payload));
                }
            }
        }
    }

    /**
     * @throws AiraloException
     * @param array $payload
     * @return void
     */
    private function validateCancelFutureOrder(array $payload): void
    {
        if (!isset($payload['request_ids']) || !is_array($payload['request_ids']) || count($payload['request_ids']) < 1) {
            throw new AiraloException('The request_ids is required, payload: ' . json_encode($payload));
        }
    }
}
