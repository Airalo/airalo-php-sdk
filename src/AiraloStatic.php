<?php

namespace Airalo;

use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\EasyAccess;
use Airalo\Helpers\Signature;
use Airalo\Resources\CurlResource;
use Airalo\Resources\MultiCurlResource;
use Airalo\Services\InstallationInstructionsService;
use Airalo\Services\OAuthService;
use Airalo\Services\OrderService;
use Airalo\Services\PackagesService;
use Airalo\Services\TopupService;
use Airalo\Tests\Mock\AiraloMock;

class AiraloStatic
{
    private static array $pool = [];
    private static Config $config;
    private static CurlResource $curl;
    private static MultiCurlResource $multiCurl;
    private static Signature $signature;
    private static OAuthService $oauth;
    private static PackagesService $packages;
    private static OrderService $order;
    private static TopupService $topup;
    private static InstallationInstructionsService $instruction;

    /**
     * @param mixed $config
     * @throws AiraloException
     */
    public static function init($config): void
    {
        try {
            self::initResources($config);
            self::initServices();

            if (empty(self::$pool)) {
                $reflection = new \ReflectionClass(self::class);

                foreach ($reflection->getProperties(\ReflectionProperty::IS_STATIC) as $property) {
                    $property->setAccessible(true);
                    if ($object = $property->getValue()) {
                        self::$pool[$property->getName()] = $object;
                    }
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
    public static function getAllPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        self::checkInitialized();

        return self::$packages->getPackages([
            'flat' => $flat,
            'limit' => $limit,
            'page' => $page,
        ]);
    }

    public static function getSimPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        self::checkInitialized();

        return self::$packages->getPackages([
            'flat' => $flat,
            'limit' => $limit,
            'page' => $page,
            'simOnly' => true,
        ]);
    }

    /**
     * @param bool $flat
     * @param mixed $limit
     * @param mixed $page
     * @return EasyAccess|null
     */
    public static function getLocalPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        self::checkInitialized();

        return self::$packages->getPackages([
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
    public static function getGlobalPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        self::checkInitialized();

        return self::$packages->getPackages([
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
    public static function getCountryPackages(string $countryCode, bool $flat = false, $limit = null): ?EasyAccess
    {
        self::checkInitialized();

        return self::$packages->getPackages([
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
    public static function order(string $packageId, int $quantity, ?string $description = null): ?EasyAccess
    {
        self::checkInitialized();

        return self::$order->createOrder([
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
    public static function orderBulk(array $packages, ?string $description = null): ?EasyAccess
    {
        self::checkInitialized();

        if (empty($packages)) {
            return null;
        }

        return self::$order->createOrderBulk($packages, $description);
    }

    /**
     * @param string $packageId
     * @param string $iccid
     * @param ?string $description
     * @return EasyAccess|null
     */
    public static function topup(string $packageId, string $iccid, ?string $description = null): ?EasyAccess
    {
        self::checkInitialized();

        return self::$topup->createTopup([
            'package_id' => $packageId,
            'iccid' => $iccid,
            'description' => $description ?? 'Topup placed via Airalo PHP SDK',
        ]);
    }

    /**
     * @param string $iccid
     * @return EasyAccess|null
     */
    public static function getSimInstructions(string $iccid): ?EasyAccess
    {
        self::checkInitialized();

        return self::$instruction->getInstructions([
            'iccid' => $iccid
        ]);
    }

    /**
     * @return AiraloMock
     */
    public static function mock(): AiraloMock
    {
        return new AiraloMock();
    }

    /**
     * @param mixed $config
     * @return void
     */
    private static function initResources($config): void
    {
        self::$config = self::$pool['config'] ?? new Config($config);
        self::$curl = self::$pool['curl'] ?? new CurlResource(self::$config);
        self::$multiCurl = self::$pool['multiCurl'] ?? new MultiCurlResource(self::$config);
        self::$signature = self::$pool['signature'] ?? new Signature(self::$config->get('client_secret'));
    }

    /**
     * @return void
     */
    private static function initServices(): void
    {
        self::$oauth = self::$pool['oauth'] ?? new OAuthService(self::$config, self::$curl, self::$signature);
        $token = self::$oauth->getAccessToken();

        self::$packages = self::$pool['packages'] ?? new PackagesService(self::$config, self::$curl, $token);
        self::$order = self::$pool['order']
            ?? new OrderService(self::$config, self::$curl, self::$multiCurl, self::$signature, $token);
        self::$instruction = self::$pool['instruction']
            ?? new InstallationInstructionsService(self::$config, self::$curl, $token);
        self::$topup = self::$pool['topup'] ?? new TopupService(self::$config, self::$curl, self::$signature, $token);
    }

    /**
     * @throws AiraloException
     * @return void
     */
    private static function checkInitialized(): void
    {
        if (empty(self::$pool)) {
            throw new AiraloException('Airalo SDK is not initialized, please call static method init() first');
        }
    }
}
