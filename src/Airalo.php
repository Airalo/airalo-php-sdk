<?php

namespace Airalo;

use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\Signature;
use Airalo\Resources\Curl;
use Airalo\Resources\MultiCurl;
use Airalo\Services\OAuth;
use Airalo\Services\Order;
use Airalo\Services\Packages;
use Airalo\Services\Topup;
class Airalo
{
    private static array $pool = [];

    //
    // Configuration and resources
    //
    private Config $config;
    private Curl $curl;
    private MultiCurl $multiCurl;
    private Signature $signature;

    //
    // Services
    //
    private OAuth $oauth;
    private Packages $packages;
    private Order $order;
    private Topup $topup;

    /**
     * @param mixed $config
     */
    public function __construct($config)
    {
        try {
            $this->initResources($config);
            $this->initServices();

            if (empty(self::$pool)) {
                foreach (get_object_vars($this) as $key => $value) {
                    self::$pool[$key] = $value;
                }
            }
        } catch (\Throwable $e) {
            self::$pool = [];

            throw new AiraloException('Airalo SDK initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * @param mixed $limit
     * @param mixed $page
     * @return array|null
     */
    public function getAllPackages($limit = null, $page = null): ?array
    {
        return $this->packages->getPackages([
            'limit' => $limit,
            'page' => $page,
        ]);
    }

    /**
     * @param mixed $limit
     * @param mixed $page
     * @return array|null
     */
    public function getLocalPackages($limit = null, $page = null): ?array
    {
        return $this->packages->getPackages([
            'limit' => $limit,
            'page' => $page,
            'type' => 'local',
        ]);
    }

    /**
     * @param mixed $limit
     * @param mixed $page
     * @return array|null
     */
    public function getGlobalPackages($limit = null, $page = null): ?array
    {
        return $this->packages->getPackages([
            'limit' => $limit,
            'page' => $page,
            'type' => 'global',
        ]);
    }

    /**
     * @param string $countryCode
     * @param mixed $limit
     * @return array|null
     */
    public function getCountryPackages(string $countryCode, $limit = null): ?array
    {
        return $this->packages->getPackages([
            'limit' => $limit,
            'country' => strtoupper($countryCode),
        ]);
    }

    /**
     * @param mixed $config
     * @return void
     */
    private function initResources($config): void
    {
        $this->config = self::$pool['config'] ?? new Config($config);
        $this->curl = self::$pool['curl'] ?? new Curl();
        $this->multiCurl = self::$pool['multiCurl'] ?? new MultiCurl();
        $this->signature = self::$pool['signature'] ?? new Signature($this->config->get('client_secret'));
    }

    /**
     * @return void
     */
    private function initServices(): void
    {
        $this->oauth = self::$pool['oauth'] ?? new OAuth($this->config, $this->curl, $this->signature);
        $token = $this->oauth->getAccessToken();

        $this->packages = self::$pool['packages'] ?? new Packages($this->config, $this->curl, $token);
        $this->order = self::$pool['order'] ?? new Order($this->config, $this->curl, $token);
        $this->topup = self::$pool['topup'] ?? new Topup($this->config, $this->curl, $token);
    }
}
