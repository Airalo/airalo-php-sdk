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

        $expected = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'airalo_' . md5($this->cacheName);
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
}
