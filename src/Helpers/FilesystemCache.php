<?php

namespace Airalo\Helpers;

use Airalo\Contracts\CacheInterface;

class FilesystemCache implements CacheInterface
{
    private const CACHE_KEY = 'airalo_';

    /**
     * @var int
     */
    private int $defaultTtl = 86400;

    /**
     * @var string
     */
    private string $cachePath;

    /**
     * @var string|null
     */
    private ?string $id = null;

    /**
     * @param string $cachePath
     */
    public function __construct(string $cachePath = '')
    {
        $this->cachePath = $cachePath !== ''
            ? rtrim($cachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR;
    }

    /**
     * @param callable $work
     * @param string $key
     * @param int $ttl
     * @return mixed
     */
    public function get(callable $work, string $key, int $ttl = 0)
    {
        $this->id = $this->getID($key);

        if (!$result = $this->cacheGet($ttl)) {
            $result = $work();

            return $this->cacheThis($result);
        }

        return $result;
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        array_map('unlink', glob($this->cachePath . self::CACHE_KEY . '*') ?: []);
    }

    /**
     * @param string $key
     * @return string
     */
    private function getID(string $key): string
    {
        return self::CACHE_KEY . md5($key);
    }

    /**
     * @param int $customTtl
     * @return mixed
     */
    private function cacheGet(int $customTtl = 0)
    {
        $file = $this->cachePath . $this->id;

        if (!file_exists($file)) {
            return false;
        }

        $now = strtotime('now');
        $ttl = $now + ($customTtl ?: $this->defaultTtl);

        if ($now - filemtime($file) > $ttl - $now) {
            unlink($file);

            return false;
        }

        $result = file_get_contents($file);

        return !$result ? false : unserialize($result);
    }

    /**
     * @param mixed $result
     * @return mixed
     */
    private function cacheThis($result)
    {
        if (!$result) {
            return null;
        }

        $data = serialize($result);
        $file = $this->cachePath . $this->id;

        file_put_contents($file, $data);
        chmod($file, 0777);

        return $result;
    }
}

