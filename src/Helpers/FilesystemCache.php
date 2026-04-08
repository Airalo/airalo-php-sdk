<?php

namespace Airalo\Helpers;

use Psr\SimpleCache\CacheInterface;

class FilesystemCache implements CacheInterface
{
    private const CACHE_KEY = 'airalo_';

    /**
     * PSR-16 reserved characters that MUST NOT appear in cache keys.
     */
    private const RESERVED_CHARACTERS = '{}()/\@:';

    /**
     * Default TTL in seconds (24 hours), applied when set()/setMultiple() receive ttl=null.
     *
     * @var int
     */
    private int $defaultTtl;

    /**
     * @var string
     */
    private string $cachePath;

    /**
     * @param string $cachePath  Directory for cache files (defaults to sys_get_temp_dir())
     * @param int    $defaultTtl Default TTL in seconds used when set() receives ttl=null
     */
    public function __construct(string $cachePath = '', int $defaultTtl = 86400)
    {
        $this->cachePath = $this->initializeCachePath($cachePath);
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * @param string $cachePath
     * @return string
     */
    private function initializeCachePath(string $cachePath): string
    {
        $path = $cachePath !== ''
            ? rtrim($cachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR;

        if (!file_exists($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Cache path "%s" could not be created.', $path));
        }

        if (!is_dir($path)) {
            throw new \RuntimeException(sprintf('Cache path "%s" is not a directory.', $path));
        }

        if (!is_writable($path)) {
            throw new \RuntimeException(sprintf('Cache path "%s" is not writable.', $path));
        }

        return $path;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws InvalidCacheKeyException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        $file = $this->filePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $raw = file_get_contents($file);

        if ($raw === false) {
            return $default;
        }

        $entry = @unserialize($raw, ['allowed_classes' => false]);

        if ($entry === false && $raw !== serialize(false)) {
            // Corrupted data – treat as cache miss and clean up
            @unlink($file);

            return $default;
        }

        if (
            !is_array($entry)
            || !array_key_exists('expiresAt', $entry)
            || !array_key_exists('value', $entry)
        ) {
            // Legacy format or unexpected structure – treat as cache miss and clean up
            @unlink($file);

            return $default;
        }

        if ($entry['expiresAt'] !== null && time() >= $entry['expiresAt']) {
            @unlink($file);

            return $default;
        }

        return $entry['value'];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null|int|\DateInterval $ttl
     * @return bool
     * @throws InvalidCacheKeyException
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        $seconds = $this->normalizeTtl($ttl);

        // PSR-16: a TTL of 0 or a negative value MUST delete the item immediately
        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }

        $effectiveTtl = $seconds ?? $this->defaultTtl;
        $expiresAt = time() + $effectiveTtl;

        $entry = serialize([
            'expiresAt' => $expiresAt,
            'value' => $value,
        ]);

        $file = $this->filePath($key);

        if (file_put_contents($file, $entry, LOCK_EX) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @return bool
     * @throws InvalidCacheKeyException
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);

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
     * @throws InvalidCacheKeyException
     */
    public function has(string $key): bool
    {
        $sentinel = new \stdClass();

        return $this->get($key, $sentinel) !== $sentinel;
    }

    /**
     * @param iterable<string> $keys
     * @param mixed $default
     * @return iterable<string, mixed>
     * @throws InvalidCacheKeyException
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $this->validateIterable($keys);

        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     * @param null|int|\DateInterval $ttl
     * @return bool
     * @throws InvalidCacheKeyException
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $this->validateIterable($values);

        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @param iterable<string> $keys
     * @return bool
     * @throws InvalidCacheKeyException
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $this->validateIterable($keys);

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

    /**
     * Validate a cache key per PSR-16 requirements.
     *
     * @param string $key
     * @return void
     * @throws InvalidCacheKeyException
     */
    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidCacheKeyException('Cache key must not be empty.');
        }

        if (preg_match('/[' . preg_quote(self::RESERVED_CHARACTERS, '/') . ']/', $key)) {
            throw new InvalidCacheKeyException(
                sprintf('Cache key "%s" contains reserved characters: %s', $key, self::RESERVED_CHARACTERS)
            );
        }
    }

    /**
     * Validate that a value is iterable (required by PSR-16 for *Multiple methods).
     *
     * @param iterable<mixed> $value
     * @return void
     * @throws InvalidCacheKeyException
     */
    private function validateIterable(iterable $value): void
    {
        // Type declaration enforces iterability; nothing else to check.
    }

    /**
     * Normalize a TTL value to seconds or null.
     *
     * @param null|int|\DateInterval $ttl
     * @return int|null  null means "use default TTL"
     */
    private function normalizeTtl(null|int|\DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof \DateInterval) {
            $now = new \DateTimeImmutable();
            return $now->add($ttl)->getTimestamp() - $now->getTimestamp();
        }

        return (int) $ttl;
    }
}
