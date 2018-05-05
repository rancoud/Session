<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Exception;

/**
 * Trait ArrayManager.
 */
trait ArrayManager
{
    /** @var array */
    protected static $flashData = [];

    abstract protected static function startSessionIfNotHasStarted();

    abstract protected static function startSessionIfNotHasStartedForceWrite();

    /**
     * @param $key
     * @param $value
     *
     * @throws Exception
     */
    public static function set(string $key, $value): void
    {
        static::startSessionIfNotHasStartedForceWrite();

        $_SESSION[$key] = $value;
    }

    /**
     * @param $key
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function has(string $key): bool
    {
        static::startSessionIfNotHasStarted();

        return array_key_exists($key, $_SESSION);
    }

    /**
     * @param $key
     * @param $value
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function hasKeyAndValue(string $key, $value): bool
    {
        static::startSessionIfNotHasStarted();

        return array_key_exists($key, $_SESSION) && $_SESSION[$key] === $value;
    }

    /**
     * @param $key
     *
     * @throws Exception
     *
     * @return mixed
     */
    public static function get(string $key)
    {
        static::startSessionIfNotHasStarted();

        return (static::has($key)) ? $_SESSION[$key] : null;
    }

    /**
     * @param $key
     *
     * @throws Exception
     */
    public static function remove(string $key): void
    {
        static::startSessionIfNotHasStartedForceWrite();

        if (static::has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public static function getAll(): array
    {
        static::startSessionIfNotHasStarted();

        return $_SESSION;
    }

    /**
     * @param $key
     *
     * @throws Exception
     *
     * @return mixed
     */
    public static function getAndRemove(string $key)
    {
        static::startSessionIfNotHasStartedForceWrite();

        $value = static::get($key);
        static::remove($key);

        return $value;
    }

    /**
     * @param $key
     * @param $value
     *
     * @throws Exception
     */
    public static function setFlash($key, $value)
    {
        static::$flashData[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public static function hasFlash($key)
    {
        return array_key_exists($key, static::$flashData);
    }

    /**
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public static function hasFlashKeyAndValue($key, $value): bool
    {
        return array_key_exists($key, static::$flashData) && static::$flashData[$key] === $value;
    }

    /**
     * @param $key
     *
     * @throws Exception
     *
     * @return mixed
     */
    public static function getFlash($key)
    {
        return (static::hasFlash($key)) ? static::$flashData[$key] : null;
    }

    /**
     * @param array $keys
     */
    public static function keepFlash(array $keys = []): void
    {
        static::startSessionIfNotHasStartedForceWrite();

        if (empty($keys)) {
            $_SESSION['flash_data'] = static::$flashData;
        } else {
            $_SESSION['flash_data'] = [];

            foreach ($keys as $key) {
                if (static::hasFlash($key)) {
                    $_SESSION['flash_data'][$key] = static::$flashData[$key];
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function restoreFlashData()
    {
        $data = static::getAndRemove('flash_data');
        if (null !== $data) {
            static::$flashData = $data;
        }
    }

    /**
     * @return array
     */
    public static function getAllFlash(): array
    {
        return static::$flashData;
    }
}
