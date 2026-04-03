<?php

namespace Airalo\Helpers;

/**
 * @deprecated Use \Airalo\Contracts\CacheInterface and \Airalo\Helpers\FilesystemCache instead.
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
     * @deprecated Use CacheInterface::get() instead.
     */
    public static function get($work, string $cacheName, int $ttl = 0)
    {
        $type = gettype($work);

        if (!in_array($type, ['object', 'callable'])) {
            $callable = function () use ($work) {
                return $work;
            };
        } else {
            $callable = $work;
        }

        return self::getInstance()->get($callable, $cacheName, $ttl);
    }

    /**
     * @return void
     *
     * @deprecated Use CacheInterface::clear() instead.
     */
    public static function clearCache(): void
    {
        self::getInstance()->clear();
    }
}

