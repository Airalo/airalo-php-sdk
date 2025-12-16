<?php
namespace Airalo\Tests;

use Airalo\Exceptions\AiraloException;
use Airalo\Services\VoucherService;
use Airalo\Services\InstallationInstructionsService;
use Airalo\Services\SimService;
use PHPUnit\Framework\TestCase;
use Airalo\Airalo;
use Airalo\Config;
use Airalo\Resources\CurlResource;
use Airalo\Resources\MultiCurlResource;
use Airalo\Helpers\Signature;
use Airalo\Services\OAuthService;
use Airalo\Services\OrderService;
use Airalo\Services\PackagesService;
use Airalo\Services\TopupService;
use Airalo\Helpers\EasyAccess;
use Airalo\Services\CompatibilityDevicesService;
use ReflectionMethod;
use ReflectionClass;

class AiraloTest extends TestCase
{
    private $configMock;
    private $curlMock;
    private $multiCurlMock;
    private $signatureMock;
    private $oauthServiceMock;
    private $packagesServiceMock;
    private $orderServiceMock;
    private $voucherServiceMock;
    private $topupServiceMock;
    private $instructionServiceMock;
    private $airalo;
    private $simServiceMock;
    private $compatibilityDevicesServiceMock;

    /**
     * @throws \ReflectionException
     * @throws AiraloException
     */
    protected function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(Config::class)
            ->setConstructorArgs(['data' => ['client_id' => 'test', 'client_secret' => 'test']])
            ->getMock();
        $this->curlMock = $this->createMock(CurlResource::class);
        $this->multiCurlMock = $this->createMock(MultiCurlResource::class);
        $this->signatureMock = $this->createMock(Signature::class);
        $this->oauthServiceMock = $this->createMock(OAuthService::class);
        $this->packagesServiceMock = $this->createMock(PackagesService::class);
        $this->orderServiceMock = $this->createMock(OrderService::class);
        $this->topupServiceMock = $this->createMock(TopupService::class);
        $this->voucherServiceMock = $this->createMock(VoucherService::class);
        $this->simServiceMock = $this->createMock(SimService::class);
        $this->voucherServiceMock = $this->createMock(VoucherService::class);
        $this->instructionServiceMock = $this->createMock(InstallationInstructionsService::class);
        $this->compatibilityDevicesServiceMock = $this->createMock(CompatibilityDevicesService::class);

        $this->oauthServiceMock
            ->method('getAccessToken')
            ->willReturn('mocked-access-token');

        $this->airalo = $this->getMockBuilder(Airalo::class)
            ->setConstructorArgs(['config' => ['client_id' => 'test', 'client_secret' => 'test']])
            ->onlyMethods(['initResources', 'initServices'])
            ->getMock();

       $this->setAiraloPropertiesAccessible();

        $this->airalo->__construct($this->configMock);
    }

    public function testGetAllPackages()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->packagesServiceMock
            ->expects($this->once())
            ->method('getPackages')
            ->with(['flat' => false, 'limit' => null, 'page' => null])
            ->willReturn($expectedResult);

        $result = $this->airalo->getAllPackages();
        $this->assertSame($expectedResult, $result);
    }

    public function testGetSimPackages()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->packagesServiceMock
            ->expects($this->once())
            ->method('getPackages')
            ->with(['flat' => false, 'limit' => null, 'page' => null, 'simOnly' => true])
            ->willReturn($expectedResult);

        $result = $this->airalo->getSimPackages();
        $this->assertSame($expectedResult, $result);
    }

    public function testGetLocalPackages()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->packagesServiceMock
            ->expects($this->once())
            ->method('getPackages')
            ->with(['flat' => false, 'limit' => null, 'page' => null, 'type' => 'local'])
            ->willReturn($expectedResult);

        $result = $this->airalo->getLocalPackages();
        $this->assertSame($expectedResult, $result);
    }

    public function testGetGlobalPackages()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->packagesServiceMock
            ->expects($this->once())
            ->method('getPackages')
            ->with(['flat' => false, 'limit' => null, 'page' => null, 'type' => 'global'])
            ->willReturn($expectedResult);

        $result = $this->airalo->getGlobalPackages();
        $this->assertSame($expectedResult, $result);
    }

    public function testGetUniversalPackages()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->packagesServiceMock
            ->expects($this->once())
            ->method('getPackages')
            ->with(['flat' => false, 'limit' => null, 'page' => null, 'type' => 'universal'])
            ->willReturn($expectedResult);

        $result = $this->airalo->getUniversalPackages();
        $this->assertSame($expectedResult, $result);
    }

    public function testGetCountryPackages()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->packagesServiceMock
            ->expects($this->once())
            ->method('getPackages')
            ->with(['flat' => false, 'limit' => null, 'country' => 'US'])
            ->willReturn($expectedResult);

        $result = $this->airalo->getCountryPackages('us');
        $this->assertSame($expectedResult, $result);
    }

    public function testOrder()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->orderServiceMock
            ->expects($this->once())
            ->method('createOrder')
            ->with([
                'package_id' => 'package-id',
                'quantity' => 1,
                'type' => 'sim',
                'description' => 'Order placed via Airalo PHP SDK'
            ])
            ->willReturn($expectedResult);

        $result = $this->airalo->order('package-id', 1);
        $this->assertSame($expectedResult, $result);
    }

    public function testOrderBulk()
    {
        $packages = [['package_id' => 'package-id-1', 'quantity' => 1]];
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->orderServiceMock
            ->expects($this->once())
            ->method('createOrderBulk')
            ->with($packages, null)
            ->willReturn($expectedResult);

        $result = $this->airalo->orderBulk($packages);
        $this->assertSame($expectedResult, $result);
    }

    public function testOrderAsync()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->orderServiceMock
            ->expects($this->once())
            ->method('createOrderAsync')
            ->with([
                'package_id' => 'package-id',
                'quantity' => 1,
                'type' => 'sim',
                'description' => 'Order placed via Airalo PHP SDK',
                'webhook_url' => null
            ])
            ->willReturn($expectedResult);

        $result = $this->airalo->orderAsync('package-id', 1);
        $this->assertSame($expectedResult, $result);
    }

    public function testOrderAsyncBulk()
    {
        $packages = ['package_id' => 1, 'package-id-1' => 1];
        $expectedResult = new EasyAccess(['test' => 1, 'test2' => 2]);

        $this->orderServiceMock
            ->expects($this->once())
            ->method('createOrderAsyncBulk')
            ->with($packages)
            ->willReturn($expectedResult);


        $result = $this->airalo->orderAsyncBulk($packages);
        $this->assertSame($expectedResult, $result);
    }

    public function testTopup()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->topupServiceMock
            ->expects($this->once())
            ->method('createTopup')
            ->with([
                'package_id' => 'package-id',
                'iccid' => 'iccid',
                'description' => 'Topup placed via Airalo PHP SDK'
            ])
            ->willReturn($expectedResult);

        $result = $this->airalo->topup('package-id', 'iccid');
        $this->assertSame($expectedResult, $result);
    }

    public function testGetSimInstructions()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->instructionServiceMock
            ->expects($this->once())
            ->method('getInstructions')
            ->with([
                'iccid' => 'iccid',
                'language' => 'en',
                ])
            ->willReturn($expectedResult);

        $result = $this->airalo->getSimInstructions('iccid');
        $this->assertSame($expectedResult, $result);

    }

    public function testSimUsage()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->simServiceMock
            ->expects($this->once())
            ->method('simUsage')
            ->with([
                'iccid' => 'iccid',
            ])
            ->willReturn($expectedResult);

        $result = $this->airalo->simUsage( 'iccid');
        $this->assertSame($expectedResult, $result);
    }

    public function testVoucherAirmoney()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->voucherServiceMock
            ->expects($this->once())
            ->method('createVoucher')
            ->with([
                'usage_limit' => 40,
                'amount' => 22,
                'quantity' => 1,
                'is_paid' => false,
                'voucher_code' => 'voucher-code',
            ])
            ->willReturn($expectedResult);

        $result = $this->airalo->voucher( 40,22,1,false,'voucher-code');
        $this->assertSame($expectedResult, $result);
    }

    public function testVoucherEsimAirmoney()
    {
        $expectedResult = $this->createMock(EasyAccess::class);
        $this->voucherServiceMock
            ->expects($this->once())
            ->method('createEsimVoucher')
            ->with([
                'vouchers' => [
                    [
                        'package_id' => 'package_slug',
                        'quantity' => 1
                    ]
                ]
            ])
            ->willReturn($expectedResult);

        $result = $this->airalo->esimVouchers([
            [
                'package_id' => 'package_slug',
                'quantity' => 1
            ]
        ]);

        $this->assertSame($expectedResult, $result);
    }

    public function testOrderWithEmailSimShare()
    {
        $expectedResult = new EasyAccess(['test' => 1, 'test2' => 2]);
        $payload = [
            'package_id' => 'test',
            'quantity' => 1,
            'type' => 'sim',
            'description' => 'Order placed via Airalo PHP SDK',
        ];
        $shareOptions = ['to_email' => 'test@test.com', 'sharing_option' => ['link']];

        $this->orderServiceMock
            ->method('createOrderWithEmailSimShare')
            ->with($payload, $shareOptions)
            ->willReturn($expectedResult);

        $result = $this->airalo->orderWithEmailSimShare('test', 1, $shareOptions);
        $this->assertSame($expectedResult, $result);
    }

    public function testGetCompatibleDevices()
    {
        $expectedResult = $this->createMock(EasyAccess::class);

        $this->compatibilityDevicesServiceMock
            ->expects($this->once())
            ->method('getCompatibleDevices')
            ->willReturn($expectedResult);

        $result = $this->airalo->getCompatibleDevices();
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @throws \ReflectionException
     */
    private function setAiraloPropertiesAccessible()
    {
        $method = new ReflectionMethod($this->airalo, 'initResources');
        $method->setAccessible(true);

        $methodInitServices = new ReflectionMethod($this->airalo, 'initServices');
        $methodInitServices->setAccessible(true);

        $reflection = new ReflectionClass(Airalo::class);
        $config = $reflection->getProperty('config');
        $config->setAccessible(true);
        $config->setValue($this->airalo, $this->configMock);

        $curl = $reflection->getProperty('packages');
        $curl->setAccessible(true);
        $curl->setValue($this->airalo, $this->packagesServiceMock);

        $curl = $reflection->getProperty('order');
        $curl->setAccessible(true);
        $curl->setValue($this->airalo, $this->orderServiceMock);

        $curl = $reflection->getProperty('instruction');
        $curl->setAccessible(true);
        $curl->setValue($this->airalo, $this->instructionServiceMock);

        $curl = $reflection->getProperty('topup');
        $curl->setAccessible(true);
        $curl->setValue($this->airalo, $this->topupServiceMock);

        $curl = $reflection->getProperty('sim');
        $curl->setAccessible(true);
        $curl->setValue($this->airalo, $this->simServiceMock);

        $curl = $reflection->getProperty('voucher');
        $curl->setAccessible(true);
        $curl->setValue($this->airalo, $this->voucherServiceMock);

        $compatibility = $reflection->getProperty('compatibilityDevicesService');
        $compatibility->setAccessible(true);
        $compatibility->setValue($this->airalo, $this->compatibilityDevicesServiceMock);
    }
}
