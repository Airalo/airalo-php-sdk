<?php

namespace Airalo\Tests\Services;

use Airalo\Config;
use Airalo\Resources\CurlResource;
use Airalo\Resources\MultiCurlResource;
use Airalo\Services\SimService;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;

class SimServiceTest extends TestCase
{
    private SimService $simService;
    private ReflectionMethod $isIccid;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $configMock = $this->getMockBuilder(Config::class)
            ->setConstructorArgs(['data' => ['client_id' => 'test', 'client_secret' => 'test']])
            ->getMock();
        $curlMock = $this->createMock(CurlResource::class);
        $multiCurlMock = $this->createMock(MultiCurlResource::class);
        $cacheMock = $this->createMock(CacheInterface::class);

        $this->simService = new SimService(
            $configMock,
            $curlMock,
            $multiCurlMock,
            'mocked-access-token',
            $cacheMock
        );

        $this->isIccid = new ReflectionMethod(SimService::class, 'isIccid');
        $this->isIccid->setAccessible(true);
    }

    /**
     * @dataProvider iccidProvider
     */
    public function testIsIccidValidatesLength($value, bool $expected): void
    {
        $this->assertSame(
            $expected,
            $this->isIccid->invoke($this->simService, $value)
        );
    }

    /**
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public function iccidProvider(): array
    {
        return [
            'too short (15 digits)'          => [str_repeat('8', 15), false],
            'minimum boundary (16 digits)'   => [str_repeat('8', 16), true],
            'typical (18 digits)'            => [str_repeat('8', 18), true],
            'maximum boundary (22 digits)'   => [str_repeat('8', 22), true],
            'too long (23 digits)'           => [str_repeat('8', 23), false],
            'non-numeric of valid length'    => [str_repeat('a', 16), false],
            'numeric with letter mixed in'   => ['89360000000013a7', false],
        ];
    }
}
