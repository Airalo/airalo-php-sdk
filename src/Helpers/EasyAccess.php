<?php

namespace Airalo\Helpers;

use Airalo\Exceptions\AiraloException;

class EasyAccess implements \ArrayAccess, \Countable
{
    private const STRING_KEY = "stringData";

    /**
     * @param mixed $data
     */
    public function __construct($data = [])
    {
        if (is_string($data)) {
            $json = json_decode($data);

            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $json;
                unset($json);
            }
        }

        if (is_object($data)) {
            $data = (array) $data;
        }

        if (is_string($data)) {
            return $this->offsetSet(self::STRING_KEY, $data);
        }

        if (!is_array($data)) {
            throw new AiraloException("Provided data is not a valid array/object!");
        }

        foreach ($data as $key => $value) {
            // recursively set objects
            if (is_array($value) || is_object($value)) {
                $value = new self($value);
            }

            $this->offsetSet($key, $value);
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        if ($this->offsetExists(self::STRING_KEY)) {
            return $this->offsetGet(self::STRING_KEY);
        }

        return json_encode($this);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count((array) $this);
    }

    /**
     * @param string $offset
     * @param string $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if (!is_null($offset)) {
            $this->{$offset} = $value;
        }
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->{$offset});
    }

    /**
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->{$offset});
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }
}
