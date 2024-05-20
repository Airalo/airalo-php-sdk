<?php
namespace Airalo\Tests;

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

class AiraloTest extends TestCase
{
    private $configMock;
    private $curlMock;
    private $multiCurlMock;
    private $signatureMock;
    private $oauthServiceMock;
    private $packagesServiceMock;
    private $orderServiceMock;
    private $topupServiceMock;
    private $airalo;

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

        $this->oauthServiceMock
            ->method('getAccessToken')
            ->willReturn('mocked-access-token');

        $this->airalo = $this->getMockBuilder(Airalo::class)
            ->setConstructorArgs(['config' => ['client_id' => 'test', 'client_secret' => 'test']])
            ->onlyMethods(['initResources', 'initServices'])
            ->getMock();

        $this->airalo->expects($this->any())
            ->method('initResources')
            ->willReturnCallback(function ($config) {
                $this->airalo->config = $this->configMock;
                $this->airalo->curl = $this->curlMock;
                $this->airalo->multiCurl = $this->multiCurlMock;
                $this->airalo->signature = $this->signatureMock;
            });

        $this->airalo->expects($this->any())
            ->method('initServices')
            ->willReturnCallback(function () {
                $this->airalo->oauth = $this->oauthServiceMock;
                $this->airalo->packages = $this->packagesServiceMock;
                $this->airalo->order = $this->orderServiceMock;
                $this->airalo->topup = $this->topupServiceMock;
            });

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
}