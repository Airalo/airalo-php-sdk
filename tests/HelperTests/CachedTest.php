<?php

namespace Airalo\Tests\HelperTests;

use PHPUnit\Framework\TestCase;
use Airalo\Helpers\Cached;
use ReflectionMethod;

class CachedTest extends TestCase
{
    private $cacheName;
    private $cacheFile;

    protected function setUp(): void
    {
        $this->cacheName = 'test_cache';
        $this->cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'airalo_' . md5($this->cacheName);
    }

    protected function tearDown(): void
    {
        @unlink($this->cacheFile);
        Cached::clearCache();
    }

    public function testGetWithCallable()
    {
        $result = Cached::get(function() {
            return 'test';
        }, $this->cacheName);

        $this->assertSame('test', $result);
    }

    public function testGetWithValue()
    {
        $result = Cached::get('test_value', $this->cacheName);

        $this->assertSame('test_value', $result);
    }

    public function testCacheHit()
    {
        $work = function() {
            return 'cache_test';
        };

        Cached::get($work, $this->cacheName);
        $cachedResult = Cached::get($work, $this->cacheName);

        $this->assertSame('cache_test', $cachedResult);
    }

    public function testCacheMiss()
    {
        $work = function() {
            return 'cache_test';
        };

        $cachedResult = Cached::get($work, $this->cacheName, -1);

        $this->assertSame('cache_test', $cachedResult);
    }

    public function testClearCache()
    {
        $work = function() {
            return 'cache_clear_test';
        };

        Cached::get($work, $this->cacheName);
        Cached::clearCache();

        $this->assertFileDoesNotExist($this->cacheFile);
    }

    public function testGetID()
    {
        $method = new ReflectionMethod(Cached::class, 'getID');
        $method->setAccessible(true);

        $cacheID = $method->invoke(null, $this->cacheName);

        $this->assertSame('airalo_' . md5($this->cacheName), $cacheID);
    }

    public function testCacheGet()
    {
        $method = new ReflectionMethod(Cached::class, 'cacheGet');
        $method->setAccessible(true);

        $work = function() {
            return 'cache_get_test';
        };

        Cached::get($work, $this->cacheName);

        $cachedResult = $method->invoke(null, 0);

        $this->assertSame('cache_get_test', $cachedResult);
    }
}
