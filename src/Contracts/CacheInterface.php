<?php

namespace Airalo\Contracts;

interface CacheInterface
{
    /**
     * Retrieve a value from cache or compute it via $work.
     *
     * @param callable $work
     * @param string $key
     * @param int $ttl
     * @return mixed
     */
    public function get(callable $work, string $key, int $ttl = 0);

    /**
     * Clear all cached entries.
     *
     * @return void
     */
    public function clear(): void;
}

