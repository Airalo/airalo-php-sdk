<?php

namespace Airalo\Services;

use Airalo\Config;
use Airalo\Constants\ApiConstants;
use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\Cached;
use Airalo\Helpers\Misc;
use Airalo\Helpers\Signature;
use Airalo\Resources\CurlResource;

class OAuthService
{
    private const CACHE_NAME = 'access_token';

    private const RETRY_LIMIT = 2;

    private Config $config;

    private array $payload;

    private CurlResource $curl;

    private Signature $signature;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param Signature $signature
     */
    public function __construct(Config $config, CurlResource $curl, Signature $signature)
    {
        $this->config = $config;

        $this->payload = $this->config->getCredentials() + [
            'grant_type' => 'client_credentials',
        ];

        $this->curl = $curl;

        $this->signature = $signature;
    }

    /**
     * @return ?string
     */
    public function getAccessToken(): ?string
    {
        $retryCount = 0;

        while ($retryCount < self::RETRY_LIMIT) {
            try {
                $token = Cached::get(function () {
                    $response = $this->curl
                        ->setHeaders([
                            'airalo-signature: ' . $this->signature->getSignature($this->payload),
                        ])
                        ->post($this->config->getUrl() . ApiConstants::TOKEN_SLUG, http_build_query($this->payload));

                    if (!$response || $this->curl->code != 200) {
                        throw new AiraloException('Access token generation failed');
                    }

                    $response = json_decode($response, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new AiraloException('Failed to parse access token response: ' . json_last_error_msg());
                    }

                    if (!isset($response['data']['access_token'])) {
                        throw new AiraloException('Access token not found in response');
                    }

                    return Misc::encrypt($response['data']['access_token'], $this->config->get('client_secret'));
                }, self::CACHE_NAME);

                return Misc::decrypt($token, $this->config->get('client_secret'));
            } catch (\Throwable $e) {
                $retryCount++;

                if ($retryCount > self::RETRY_LIMIT) {
                    throw new AiraloException('Failed to get access token from API: ' . $e->getMessage());
                }

                usleep(500000);
            }
        }

        return null;
    }
}
