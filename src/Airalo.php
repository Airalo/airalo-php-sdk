<?php

namespace Airalo;

use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\EasyAccess;
use Airalo\Helpers\Signature;
use Airalo\Resources\CurlResource;
use Airalo\Resources\MultiCurlResource;
use Airalo\Services\OAuthService;
use Airalo\Services\OrderService;
use Airalo\Services\PackagesService;
use Airalo\Services\TopupService;

class Airalo
{
    private static array $pool = [];

    //
    // Configuration and resources
    //
    private Config $config;
    private CurlResource $curl;
    private MultiCurlResource $multiCurl;
    private Signature $signature;

    //
    // Services
    //
    private OAuthService $oauth;
    private PackagesService $packages;
    private OrderService $order;
    private TopupService $topup;

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
     * @param bool $flat
     * @param mixed $limit
     * @param mixed $page
     * @return EasyAccess|null
     */
    public function getAllPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        return $this->packages->getPackages([
            'flat' => $flat,
            'limit' => $limit,
            'page' => $page,
        ]);
    }

    /**
     * @param bool $flat
     * @param mixed $limit
     * @param mixed $page
     * @return EasyAccess|null
     */
    public function getLocalPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        return $this->packages->getPackages([
            'flat' => $flat,
            'limit' => $limit,
            'page' => $page,
            'type' => 'local',
        ]);
    }

    /**
     * @param bool $flat
     * @param mixed $limit
     * @param mixed $page
     * @return EasyAccess|null
     */
    public function getGlobalPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        return $this->packages->getPackages([
            'flat' => $flat,
            'limit' => $limit,
            'page' => $page,
            'type' => 'global',
        ]);
    }

    /**
     * @param string $countryCode
     * @param bool $flat
     * @param mixed $limit
     * @return EasyAccess|null
     */
    public function getCountryPackages(string $countryCode, bool $flat = false, $limit = null): ?EasyAccess
    {
        return $this->packages->getPackages([
            'flat' => $flat,
            'limit' => $limit,
            'country' => strtoupper($countryCode),
        ]);
    }

    /**
     * @param string $packageId
     * @param int $quantity
     * @param ?string $description
     * @return EasyAccess|null
     */
    public function order(string $packageId, int $quantity, ?string $description = null): ?EasyAccess
    {
        return $this->order->createOrder([
            'package_id' => $packageId,
            'quantity' => $quantity,
            'type' => 'sim',
            'description' => $description ?? 'Order placed via Airalo PHP SDK',
        ]);
    }

    /**
     * @param array $packages
     * @param ?string $description
     * @return EasyAccess|null
     */
    public function orderBulk(array $packages, ?string $description = null): ?EasyAccess
    {
        if (empty($packages)) {
            return null;
        }

        return $this->order->createOrderBulk($packages, $description);
    }

    /**
     * @param string $packageId
     * @param int $iccid
     * @param ?string $description
     * @return EasyAccess|null
     */
    public function topup(string $packageId, string $iccid, ?string $description = null): ?EasyAccess
    {
        return $this->topup->createTopup([
            'package_id' => $packageId,
            'iccid' => $iccid,
            'description' => $description ?? 'Topup placed via Airalo PHP SDK',
        ]);
    }

    /**
     * @param mixed $config
     * @return void
     */
    private function initResources($config): void
    {
        $this->config = self::$pool['config'] ?? new Config($config);
        $this->curl = self::$pool['curl'] ?? new CurlResource($this->config);
        $this->multiCurl = self::$pool['multiCurl'] ?? new MultiCurlResource($this->config);
        $this->signature = self::$pool['signature'] ?? new Signature($this->config->get('client_secret'));
    }

    /**
     * @return void
     */
    private function initServices(): void
    {
        $this->oauth = self::$pool['oauth'] ?? new OAuthService($this->config, $this->curl, $this->signature);
        $token = $this->oauth->getAccessToken();

        $this->packages = self::$pool['packages'] ?? new PackagesService($this->config, $this->curl, $token);
        $this->order = self::$pool['order']
            ?? new OrderService($this->config, $this->curl, $this->multiCurl, $this->signature, $token);
        $this->topup = self::$pool['topup'] ?? new TopupService($this->config, $this->curl, $this->signature, $token);
    }
}
