<?php

namespace Airalo\Tests\HelperTests;

use PHPUnit\Framework\TestCase;
use Airalo\Helpers\Cached;
use Airalo\Helpers\FilesystemCache;
use ReflectionMethod;

class CachedTest extends TestCase
{
    private $cacheName;
    private $cacheFile;
    private FilesystemCache $filesystemCache;

    protected function setUp(): void
    {
        $this->cacheName = 'test_cache';
        $this->cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'airalo_' . md5($this->cacheName);
        $this->filesystemCache = new FilesystemCache();
    }

    protected function tearDown(): void
    {
        @unlink($this->cacheFile);
        $this->filesystemCache->clear();
    }

    // -- Deprecated Cached facade tests (backward-compat) --

    public function testDeprecatedGetWithCallable()
    {
        $result = Cached::get(function() {
            return 'test';
        }, $this->cacheName);

        $this->assertSame('test', $result);
    }

    public function testDeprecatedGetWithValue()
    {
        $result = Cached::get('test_value', $this->cacheName);

        $this->assertSame('test_value', $result);
    }

    public function testDeprecatedClearCache()
    {
        Cached::get(function() {
            return 'cache_clear_test';
        }, $this->cacheName);
        Cached::clearCache();

        $this->assertFileDoesNotExist($this->cacheFile);
    }

    // -- FilesystemCache tests --

    public function testGetWithCallable()
    {
        $result = $this->filesystemCache->get(function() {
            return 'test';
        }, $this->cacheName);

        $this->assertSame('test', $result);
    }

    public function testCacheHit()
    {
        $work = function() {
            return 'cache_test';
        };

        $this->filesystemCache->get($work, $this->cacheName);
        $cachedResult = $this->filesystemCache->get($work, $this->cacheName);

        $this->assertSame('cache_test', $cachedResult);
    }

    public function testCacheMiss()
    {
        $work = function() {
            return 'cache_test';
        };

        $cachedResult = $this->filesystemCache->get($work, $this->cacheName, -1);

        $this->assertSame('cache_test', $cachedResult);
    }

    public function testClearCache()
    {
        $work = function() {
            return 'cache_clear_test';
        };

        $this->filesystemCache->get($work, $this->cacheName);
        $this->filesystemCache->clear();

        $this->assertFileDoesNotExist($this->cacheFile);
    }

    public function testGetID()
    {
        $method = new ReflectionMethod(FilesystemCache::class, 'getID');
        $method->setAccessible(true);

        $cacheID = $method->invoke($this->filesystemCache, $this->cacheName);

        $this->assertSame('airalo_' . md5($this->cacheName), $cacheID);
    }

    public function testCacheGet()
    {
        $method = new ReflectionMethod(FilesystemCache::class, 'cacheGet');
        $method->setAccessible(true);

        $work = function() {
            return 'cache_get_test';
        };

        $this->filesystemCache->get($work, $this->cacheName);

        $cachedResult = $method->invoke($this->filesystemCache, 0);

        $this->assertSame('cache_get_test', $cachedResult);
    }

    public function testFilesystemCacheImplementsCacheInterface()
    {
        $this->assertInstanceOf(\Airalo\Contracts\CacheInterface::class, $this->filesystemCache);
    }

    public function testFilesystemCacheClear()
    {
        $this->filesystemCache->get(function () {
            return 'clear_test';
        }, $this->cacheName);

        $this->filesystemCache->clear();

        $this->assertFileDoesNotExist($this->cacheFile);
    }
}
