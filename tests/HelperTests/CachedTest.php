<?php

namespace Airalo\Tests\HelperTests;

use PHPUnit\Framework\TestCase;
use Airalo\Helpers\Cached;
use Airalo\Helpers\FilesystemCache;
use Airalo\Helpers\InvalidCacheKeyException;
use ReflectionMethod;

class CachedTest extends TestCase
{
    private $cacheName;
    private $cacheFile;
    private string $tmpDir;
    private FilesystemCache $filesystemCache;

    protected function setUp(): void
    {
        $this->cacheName = 'test_cache';
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'airalo_test_' . uniqid('', true);
        mkdir($this->tmpDir, 0700, true);
        $this->filesystemCache = new FilesystemCache($this->tmpDir);
        $this->cacheFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'airalo_' . md5($this->cacheName);
    }

    protected function tearDown(): void
    {
        $this->filesystemCache->clear();
        @rmdir($this->tmpDir);
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

        $defaultCacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'airalo_' . md5($this->cacheName);
        $this->assertFileDoesNotExist($defaultCacheFile);
    }

    // -- FilesystemCache PSR-16 tests --

    public function testSetAndGet()
    {
        $this->filesystemCache->set($this->cacheName, 'test_value');

        $result = $this->filesystemCache->get($this->cacheName);

        $this->assertSame('test_value', $result);
    }

    public function testGetReturnsDefaultOnMiss()
    {
        $result = $this->filesystemCache->get('nonexistent_key', 'default_val');

        $this->assertSame('default_val', $result);
    }

    public function testGetReturnsNullOnMissByDefault()
    {
        $result = $this->filesystemCache->get('nonexistent_key');

        $this->assertNull($result);
    }

    public function testCacheHit()
    {
        $this->filesystemCache->set($this->cacheName, 'cache_test');

        $cachedResult = $this->filesystemCache->get($this->cacheName);

        $this->assertSame('cache_test', $cachedResult);
    }

    public function testDelete()
    {
        $this->filesystemCache->set($this->cacheName, 'to_delete');
        $this->filesystemCache->delete($this->cacheName);

        $this->assertNull($this->filesystemCache->get($this->cacheName));
    }

    public function testHas()
    {
        $this->assertFalse($this->filesystemCache->has($this->cacheName));

        $this->filesystemCache->set($this->cacheName, 'exists');

        $this->assertTrue($this->filesystemCache->has($this->cacheName));
    }

    public function testClearCache()
    {
        $this->filesystemCache->set($this->cacheName, 'cache_clear_test');
        $this->filesystemCache->clear();

        $this->assertFileDoesNotExist($this->cacheFile);
    }

    public function testFilePath()
    {
        $method = new ReflectionMethod(FilesystemCache::class, 'filePath');
        $method->setAccessible(true);

        $path = $method->invoke($this->filesystemCache, $this->cacheName);

        $expected = $this->tmpDir . DIRECTORY_SEPARATOR . 'airalo_' . md5($this->cacheName);
        $this->assertSame($expected, $path);
    }

    public function testFilesystemCacheImplementsPsr16()
    {
        $this->assertInstanceOf(\Psr\SimpleCache\CacheInterface::class, $this->filesystemCache);
    }

    public function testSetMultipleAndGetMultiple()
    {
        $this->filesystemCache->setMultiple([
            'key_a' => 'val_a',
            'key_b' => 'val_b',
        ]);

        $results = $this->filesystemCache->getMultiple(['key_a', 'key_b', 'key_c'], 'miss');

        $this->assertSame('val_a', $results['key_a']);
        $this->assertSame('val_b', $results['key_b']);
        $this->assertSame('miss', $results['key_c']);
    }

    public function testDeleteMultiple()
    {
        $this->filesystemCache->setMultiple([
            'del_a' => 1,
            'del_b' => 2,
        ]);

        $this->filesystemCache->deleteMultiple(['del_a', 'del_b']);

        $this->assertNull($this->filesystemCache->get('del_a'));
        $this->assertNull($this->filesystemCache->get('del_b'));
    }

    // -- Per-key TTL tests --

    public function testSetWithExplicitTtlStoresPerKeyExpiry()
    {
        $before = time();
        $this->filesystemCache->set('short_ttl', 'value_a', 3600);
        $this->filesystemCache->set('long_ttl', 'value_b', 7200);

        // Both should be retrievable immediately
        $this->assertSame('value_a', $this->filesystemCache->get('short_ttl'));
        $this->assertSame('value_b', $this->filesystemCache->get('long_ttl'));

        // Verify each key has its own distinct expiry time stored
        $filePath = new ReflectionMethod(FilesystemCache::class, 'filePath');
        $filePath->setAccessible(true);

        $shortEntry = unserialize(
            file_get_contents($filePath->invoke($this->filesystemCache, 'short_ttl')),
            ['allowed_classes' => false]
        );
        $longEntry = unserialize(
            file_get_contents($filePath->invoke($this->filesystemCache, 'long_ttl')),
            ['allowed_classes' => false]
        );

        $this->assertGreaterThanOrEqual($before + 3600, $shortEntry['expiresAt']);
        $this->assertGreaterThanOrEqual($before + 7200, $longEntry['expiresAt']);
        $this->assertLessThan($longEntry['expiresAt'], $shortEntry['expiresAt']);
    }

    public function testSetWithNullTtlUsesDefaultTtl()
    {
        $this->filesystemCache->set($this->cacheName, 'value', null);

        $this->assertSame('value', $this->filesystemCache->get($this->cacheName));
    }

    public function testSetWithZeroTtlDeletesItem()
    {
        $this->filesystemCache->set($this->cacheName, 'value');

        // PSR-16: ttl=0 MUST delete the item
        $this->filesystemCache->set($this->cacheName, 'new_value', 0);

        $this->assertNull($this->filesystemCache->get($this->cacheName));
    }

    public function testSetWithNegativeTtlDeletesItem()
    {
        $this->filesystemCache->set($this->cacheName, 'value');

        // PSR-16: negative ttl MUST delete the item
        $this->filesystemCache->set($this->cacheName, 'new_value', -1);

        $this->assertNull($this->filesystemCache->get($this->cacheName));
    }

    public function testSetWithDateIntervalTtl()
    {
        $interval = new \DateInterval('PT1H'); // 1 hour
        $this->filesystemCache->set($this->cacheName, 'interval_val', $interval);

        $this->assertSame('interval_val', $this->filesystemCache->get($this->cacheName));
    }

    // -- Key validation tests --

    public function testGetWithEmptyKeyThrowsException()
    {
        $this->expectException(InvalidCacheKeyException::class);

        $this->filesystemCache->get('');
    }

    public function testSetWithEmptyKeyThrowsException()
    {
        $this->expectException(InvalidCacheKeyException::class);

        $this->filesystemCache->set('', 'value');
    }

    public function testGetWithReservedCharactersThrowsException()
    {
        $this->expectException(InvalidCacheKeyException::class);

        $this->filesystemCache->get('invalid{key}');
    }

    public function testSetWithReservedCharactersThrowsException()
    {
        $this->expectException(InvalidCacheKeyException::class);

        $this->filesystemCache->set('invalid@key', 'value');
    }

    public function testDeleteWithReservedCharactersThrowsException()
    {
        $this->expectException(InvalidCacheKeyException::class);

        $this->filesystemCache->delete('invalid/key');
    }

    public function testHasWithReservedCharactersThrowsException()
    {
        $this->expectException(InvalidCacheKeyException::class);

        $this->filesystemCache->has('invalid:key');
    }

    public function testInvalidCacheKeyExceptionImplementsPsr16Interface()
    {
        $exception = new InvalidCacheKeyException('test');

        $this->assertInstanceOf(\Psr\SimpleCache\InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    // -- Constructor defaultTtl parameter test --

    public function testCustomDefaultTtl()
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'airalo_test_' . uniqid('', true);
        mkdir($tmpDir, 0700, true);
        $cache = new FilesystemCache($tmpDir, 120);
        $cache->set('custom_ttl_key', 'value');

        $this->assertSame('value', $cache->get('custom_ttl_key'));

        $cache->clear();
        @rmdir($tmpDir);
    }
}
