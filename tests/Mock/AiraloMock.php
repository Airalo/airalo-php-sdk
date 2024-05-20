<?php

namespace Airalo\Tests\Mock;

use Airalo\Airalo;
use Airalo\Config;
use Airalo\Helpers\Signature;
use Airalo\Resources\CurlResource;
use Airalo\Resources\MultiCurlResource;
use Airalo\Services\OAuthService;
use Airalo\Services\OrderService;
use Airalo\Services\PackagesService;
use Airalo\Services\TopupService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use ReflectionClass;

class AiraloMock extends TestCase
{
    public $airalo;
    private $configMock;
    private $curlMock;
    private $multiCurlMock;
    private $signatureMock;
    private $oauthServiceMock;
    private $packagesServiceMock;
    private $orderServiceMock;
    private $topupServiceMock;

    /**
     * @throws ReflectionException
     */
    public function __construct()
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

        $this->setAiraloPropertiesAccessible();
    }

    public function getMockedAiralo(): MockObject
    {
        return $this->airalo;
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

        $curl = $reflection->getProperty('topup');
        $curl->setAccessible(true);
        $curl->setValue($this->airalo, $this->topupServiceMock);
    }
}