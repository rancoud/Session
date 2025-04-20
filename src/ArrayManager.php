<?php

declare(strict_types=1);

namespace Rancoud\Session;

/**
 * Trait ArrayManager.
 */
trait ArrayManager
{
    protected static array $flashData = [];

    /** @throws SessionException */
    abstract protected static function startSessionIfNotHasStarted();

    /** @throws SessionException */
    abstract protected static function startSessionIfNotHasStartedForceWrite();

    /**
     * @throws SessionException
     */
    public static function set(string $key, $value): void
    {
        static::startSessionIfNotHasStartedForceWrite();

        $_SESSION[$key] = $value;
    }

    /** @throws SessionException */
    public static function has(string $key): bool
    {
        static::startSessionIfNotHasStarted();

        return \array_key_exists($key, $_SESSION);
    }

    /**
     * @throws SessionException
     */
    public static function hasKeyAndValue(string $key, $value): bool
    {
        static::startSessionIfNotHasStarted();

        return \array_key_exists($key, $_SESSION) && $_SESSION[$key] === $value;
    }

    /** @throws SessionException */
    public static function get(string $key)
    {
        static::startSessionIfNotHasStarted();

        return (static::has($key)) ? $_SESSION[$key] : null;
    }

    /** @throws SessionException */
    public static function remove(string $key): void
    {
        static::startSessionIfNotHasStartedForceWrite();

        if (static::has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /** @throws SessionException */
    public static function getAll(): array
    {
        static::startSessionIfNotHasStarted();

        return $_SESSION;
    }

    /** @throws SessionException */
    public static function getAndRemove(string $key)
    {
        static::startSessionIfNotHasStartedForceWrite();

        $value = static::get($key);
        static::remove($key);

        return $value;
    }

    public static function setFlash(string $key, $value): void
    {
        static::$flashData[$key] = $value;
    }

    public static function hasFlash(string $key): bool
    {
        return \array_key_exists($key, static::$flashData);
    }

    public static function hasFlashKeyAndValue(string $key, $value): bool
    {
        return \array_key_exists($key, static::$flashData) && static::$flashData[$key] === $value;
    }

    public static function getFlash(string $key)
    {
        return (static::hasFlash($key)) ? static::$flashData[$key] : null;
    }

    public static function removeFlash(string $key): void
    {
        if (static::hasFlash($key)) {
            unset(static::$flashData[$key]);
        }
    }

    /** @throws SessionException */
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

    public static function getAllFlash(): array
    {
        return static::$flashData;
    }
}
