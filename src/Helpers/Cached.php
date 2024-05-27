<?php

namespace Airalo\Helpers;

final class Cached
{
    private const CACHE_KEY = 'airalo_';

    /**
     * @var mixed
     */
    private static $id = null;

    /**
     * @var integer
     */
    private static $ttl = 86400;

    private static string $cachePath = '';

    private static string $cacheName = '';

    /**
     * @param mixed $work
     * @param string $cacheName
     * @param int $ttl
     * @return mixed
     */
    public static function get($work, string $cacheName, int $ttl = 0)
    {
        self::init($cacheName);

        self::$id = self::getID($cacheName);

        $type = gettype($work);
        if (!$result = self::cacheGet($ttl)) {
            $result = in_array($type, ['object', 'callable'])
                ? $work()
                : $work;

            return self::cacheThis($result);
        }

        return $result;
    }

    /**
     * @return void
     */
    public static function clearCache(): void
    {
        self::init();

        array_map('unlink', glob(self::$cachePath . self::CACHE_KEY . '*'));
    }

    /**
     * @param string $cacheName
     * @return void
     */
    private static function init(string $cacheName = ''): void
    {
        if (self::$cachePath == '') {
            self::$cachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        }

        if ($cacheName != '') {
            self::$cacheName = $cacheName;
        }
    }

    /**
     * @param string $key
     * @return string
     */
    private static function getID(string $key): string
    {
        return self::CACHE_KEY . md5($key);
    }

    /**
     * @param int $customTtl
     * @return mixed
     */
    private static function cacheGet(int $customTtl = 0)
    {
        $file = self::$cachePath . self::$id;

        if (!file_exists($file)) {
            return false;
        }

        $now = strtotime('now');
        $ttl = $now + ($customTtl ?: self::$ttl);

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
    private static function cacheThis($result)
    {
        if (!$result) {
            return;
        }

        $data = serialize($result);
        $file = self::$cachePath . self::$id;

        file_put_contents($file, $data);
        chmod($file, 0777);

        return $result;
    }
}
