<?php

namespace Airalo;

use Airalo\Exceptions\AiraloException;
use Airalo\Helpers\EasyAccess;
use Airalo\Helpers\Signature;
use Airalo\Resources\CurlResource;
use Airalo\Resources\MultiCurlResource;
use Airalo\Services\CatalogService;
use Airalo\Services\ExchangeRatesService;
use Airalo\Services\FutureOrderService;
use Airalo\Services\InstallationInstructionsService;
use Airalo\Services\OAuthService;
use Airalo\Services\OrderService;
use Airalo\Services\PackagesService;
use Airalo\Services\SimService;
use Airalo\Services\TopupService;
use Airalo\Services\VoucherService;
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
    private static VoucherService $voucher;
    private static TopupService $topup;
    private static InstallationInstructionsService $instruction;
    private static SimService $sim;
    private static ExchangeRatesService $exchangeRates;
    private static FutureOrderService $futureOrders;
    private static CatalogService $catalogService;

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
    public static function getAllPackages(bool $flat = false, $limit = null, $page = null, string $locale = 'en'): ?EasyAccess
    {
        self::checkInitialized();

        return self::$packages->getPackages([
            'flat' => $flat,
            'limit' => $limit,
            'page' => $page,
        ], $locale);
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
     * @param string $packageId
     * @param int $quantity
     * @param array $esimCloud
     * @param ?string $description
     * @return EasyAccess|null
     */
    public static function orderWithEmailSimShare(string $packageId, int $quantity, array $esimCloud, ?string $description = null): ?EasyAccess
    {
        self::checkInitialized();

        return self::$order->createOrderWithEmailSimShare(
            [
                'package_id' => $packageId,
                'quantity' => $quantity,
                'type' => 'sim',
                'description' => $description ?? 'Order placed via Airalo PHP SDK',
            ],
            $esimCloud
        );
    }

    /**
     * @param string $packageId
     * @param int $quantity
     * @param ?string $webhookUrl
     * @param ?string $description
     * @return EasyAccess|null
     */
    public static function orderAsync(string $packageId, int $quantity, ?string $webhookUrl = null, ?string $description = null): ?EasyAccess
    {
        self::checkInitialized();

        return self::$order->createOrderAsync([
            'package_id' => $packageId,
            'quantity' => $quantity,
            'type' => 'sim',
            'description' => $description ?? 'Order placed via Airalo PHP SDK',
            'webhook_url' => $webhookUrl,
        ]);
    }

    /**
     * @param int $usageLimit
     * @param int $amount
     * @param int $quantity
     * @param ?bool $isPaid
     * @param ?string $voucherCode
     * @return EasyAccess|null
     */
    public static function voucher(int $usageLimit, int $amount, int $quantity, ?bool $isPaid = false, string $voucherCode = null): ?EasyAccess
    {
        self::checkInitialized();

        return self::$voucher->createVoucher([
            'voucher_code' => $voucherCode,
            'usage_limit' => $usageLimit,
            'amount' => $amount,
            'quantity' => $quantity,
            'is_paid' => $isPaid
        ]);
    }

    /**
     * @param array<int, array{package_id: string, quantity: int}> $vouchers
     * @return EasyAccess|null
     * @throws AiraloException
     */
    public static function esimVouchers(array $vouchers): ?EasyAccess
    {
        self::checkInitialized();

        return self::$voucher->createEsimVoucher([
            'vouchers' => $vouchers
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
     * @param array $packages
     * @param array $esimCloud
     * @param ?string $description
     * @return EasyAccess|null
     */
    public static function orderBulkWithEmailSimShare(array $packages, array $esimCloud, ?string $description = null): ?EasyAccess
    {
        self::checkInitialized();

        if (empty($packages)) {
            return null;
        }

        return self::$order->createOrderBulkWithEmailSimShare($packages, $esimCloud, $description);
    }

    /**
     * @param array $packages
     * @param ?string $webhookUrl
     * @param ?string $description
     * @return EasyAccess|null
     */
    public static function orderAsyncBulk(array $packages, ?string $webhookUrl = null, ?string $description = null): ?EasyAccess
    {
        self::checkInitialized();

        if (empty($packages)) {
            return null;
        }

        return self::$order->createOrderAsyncBulk($packages, $webhookUrl, $description);
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
    public static function getSimInstructions(string $iccid,string $lang = 'en'): ?EasyAccess
    {
        self::checkInitialized();

        return self::$instruction->getInstructions([
            'iccid' => $iccid,
            'language' => $lang
        ]);
    }

    /**
     * @param string $iccid
     * @return EasyAccess|null
     */
    public static function simUsage(string $iccid): ?EasyAccess
    {
        return self::$sim->simUsage([
            'iccid' => $iccid
        ]);
    }

    /**
     * @param array<string> $iccids
     * @return mixed
     */
    public static function simUsageBulk(array $iccids)
    {
        return self::$sim->simUsageBulk($iccids);
    }

    /**
     * @param string $iccid
     * @return EasyAccess|null
     */
    public static function getSimTopups(string $iccid): ?EasyAccess
    {
        return self::$sim->simTopups([
            'iccid' => $iccid
        ]);
    }

    /**
     * @param string $iccid
     * @return EasyAccess|null
     */
    public static function getSimPackageHistory(string $iccid): ?EasyAccess
    {
        return self::$sim->simPackageHistory([
            'iccid' => $iccid
        ]);
    }

    /**
     * @param string|null $date
     * @param string|null $source
     * @param string|null $from
     * @param string|null $to
     * @return EasyAccess|null
     */
    public static function getExchangeRates(?string $date=null, ?string $source=null, ?string $from=null, ?string $to=null): ?EasyAccess
    {
        return self::$exchangeRates->exchangeRates([
            'date' => $date,
            'source' => $source,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * @param string $packageId
     * @param int $quantity
     * @param string $dueDate
     * @param string|null $webhookUrl
     * @param string|null $description
     * @param string|null $brandSettingsName
     * @param string|null $toEmail
     * @param array|null $sharingOption
     * @param array|null $copyAddress
     * @return EasyAccess|null
     * @throws AiraloException
     */
    public static function createFutureOrder(
        string  $packageId,
        int     $quantity,
        string  $dueDate,
        ?string $webhookUrl = null,
        ?string $description = null,
        ?string $brandSettingsName = null,
        ?string $toEmail = null,
        ?array  $sharingOption = null,
        ?array  $copyAddress = null
    ): ?EasyAccess
    {
        return self::$futureOrders->createFutureOrder([
            'package_id' => $packageId,
            'quantity' => $quantity,
            'due_date' => $dueDate,
            'webhook_url' => $webhookUrl,
            'description' => $description ?? 'Future order placed via Airalo PHP SDK',
            'brand_settings_name' => $brandSettingsName,
            'to_email' => $toEmail,
            'sharing_option' => $sharingOption,
            'copy_address' => $copyAddress,
        ]);
    }

    /**
     * @param array $requestIds
     * @return EasyAccess|null
     * @throws AiraloException
     */
    public static function cancelFutureOrder(array $requestIds): ?EasyAccess
    {
        return self::$futureOrders->cancelFutureOrder([
            'request_ids' => $requestIds,
        ]);
    }

    /**
     * @param int $page
     * @return EasyAccess|null
     */
    public static function getCatalogOverrides(int $page = 1): ?EasyAccess
    {
        return self::$catalogService->catalogsBulk(['page' => $page]);
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
     * @throws AiraloException
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
        self::$voucher = self::$pool['voucher']
            ?? new VoucherService(self::$config, self::$curl, self::$signature, $token);
        self::$topup = self::$pool['topup'] ?? new TopupService(self::$config, self::$curl, self::$signature, $token);
        self::$sim = self::$pool['sim'] ?? new SimService(self::$config, self::$curl, self::$multiCurl, $token);
        self::$exchangeRates = self::$pool['exchangeRates'] ?? new ExchangeRatesService(self::$config, self::$curl, $token);
        self::$futureOrders = self::$pool['futureOrders'] ?? new FutureOrderService(self::$config, self::$curl, self::$signature, $token);
        self::$catalogService = self::$pool['catalogService'] ?? new CatalogService(self::$config, self::$curl, $token);
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
