<?php

namespace Airalo\Helpers;

final class Cached
{
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
     * @return mixed
     */
    public static function get($work, string $cacheName)
    {
        self::init($cacheName);

        self::$id = self::getID($cacheName);


        $type = gettype($work);
        if (!$result = self::cacheGet()) {
            $result = in_array($type, ['object', 'callable'])
                ? $work()
                : $work;

            return self::cacheThis($result);
        }

        return $result;
    }

    /**
     * @param string $cacheName
     * @return void
     */
    public static function clearCache(string $cacheName): void
    {
        self::init($cacheName);

        @unlink(self::$cachePath . self::getID(self::$cacheName));
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
        return md5($key);
    }

    /**
     * @return mixed
     */
    private static function cacheGet()
    {
        $file = self::$cachePath . self::$id;

        if (!file_exists($file)) {
            return false;
        }

        $now = strtotime('now');
        $ttl = is_string(self::$ttl)
            ? strtotime(self::$ttl)
            : $now + self::$ttl;

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
        $data = serialize($result);
        $file = self::$cachePath . self::$id;

        file_put_contents($file, $data);
        chmod($file, 0777);

        return $result;
    }
}
