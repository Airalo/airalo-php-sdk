<?php

namespace Airalo\Helpers;

/**
 * @deprecated Use Psr\SimpleCache\CacheInterface and \Airalo\Helpers\FilesystemCache instead.
 *             This static facade will be removed in a future major version.
 */
class Cached
{
    /**
     * @var FilesystemCache|null
     */
    private static ?FilesystemCache $instance = null;

    /**
     * @return FilesystemCache
     */
    private static function getInstance(): FilesystemCache
    {
        if (self::$instance === null) {
            self::$instance = new FilesystemCache();
        }

        return self::$instance;
    }

    /**
     * @param mixed $work
     * @param string $cacheName
     * @param int $ttl
     * @return mixed
     *
     * @deprecated Use Psr\SimpleCache\CacheInterface::get() instead.
     */
    public static function get($work, string $cacheName, int $ttl = 0)
    {
        $callable = is_callable($work)
            ? $work
            : function () use ($work) {
                return $work;
            };

        $cache = self::getInstance();
        $cached = $cache->get($cacheName);

        if ($cached !== null) {
            return $cached;
        }

        $result = $callable();

        if ($result !== null) {
            $cache->set($cacheName, $result, $ttl ?: null);
        }

        return $result;
    }

    /**
     * @return void
     *
     * @deprecated Use Psr\SimpleCache\CacheInterface::clear() instead.
     */
    public static function clearCache(): void
    {
        self::getInstance()->clear();
    }
}
