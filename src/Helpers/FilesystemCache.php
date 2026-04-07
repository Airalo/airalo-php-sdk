<?php

namespace Airalo\Helpers;

use Psr\SimpleCache\CacheInterface;

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
     * @param string $cachePath
     */
    public function __construct(string $cachePath = '')
    {
        $this->cachePath = $cachePath !== ''
            ? rtrim($cachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $file = $this->filePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $now = time();

        if ($now - filemtime($file) > $this->defaultTtl) {
            @unlink($file);

            return $default;
        }

        $result = file_get_contents($file);

        return $result === false ? $default : unserialize($result);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null|int|\DateInterval $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null): bool
    {
        $data = serialize($value);
        $file = $this->filePath($key);

        if (file_put_contents($file, $data) === false) {
            return false;
        }

        chmod($file, 0777);

        if ($ttl !== null) {
            $seconds = $ttl instanceof \DateInterval
                ? (int) (new \DateTime('@0'))->add($ttl)->getTimestamp()
                : (int) $ttl;

            if ($seconds > 0) {
                $this->defaultTtl = $seconds;
            }
        }

        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key): bool
    {
        $file = $this->filePath($key);

        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        $files = glob($this->cachePath . self::CACHE_KEY . '*');

        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * @param iterable $keys
     * @param mixed $default
     * @return iterable
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable $values
     * @param null|int|\DateInterval $ttl
     * @return bool
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple($keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @param string $key
     * @return string
     */
    private function filePath(string $key): string
    {
        return $this->cachePath . self::CACHE_KEY . md5($key);
    }
}
