<?php

namespace Airalo\Helpers;

use Psr\SimpleCache\CacheInterface;

trait CacheTrait
{
    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * Retrieve a value from cache or compute it.
     *
     * @param callable $work
     * @param string $key
     * @param int $ttl
     * @return mixed
     */
    private function cacheRemember(callable $work, string $key, int $ttl = 0)
    {
        $cached = $this->cache->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $result = $work();

        if ($result !== null) {
            $this->cache->set($key, $result, $ttl ?: null);
        }

        return $result;
    }
}

