<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Predis\Client as PredisClient;
use Rancoud\Database\Configurator;
use Rancoud\Database\Database as DB;

abstract class DriverManager
{
    protected static ?\SessionHandlerInterface $driver = null;

    /** @throws SessionException */
    abstract protected static function throwExceptionIfHasStarted();

    abstract protected static function getLifetimeForRedis();

    /** @throws SessionException */
    protected static function configureDriver(): void
    {
        if (empty(static::$driver)) {
            static::useDefaultDriver();
        }
    }

    /** @throws SessionException */
    public static function useDefaultDriver(): void
    {
        static::throwExceptionIfHasStarted();

        static::$driver = new \SessionHandler();
    }

    /** @throws SessionException */
    public static function useDefaultEncryptionDriver(string $key, ?string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new DefaultEncryption();
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /** @throws SessionException */
    public static function useFileDriver(): void
    {
        static::throwExceptionIfHasStarted();

        static::$driver = new File();
    }

    /** @throws SessionException */
    public static function useFileEncryptionDriver(string $key, ?string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new FileEncryption();
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /** @throws SessionException */
    public static function useNewDatabaseDriver(array|Configurator $configuration): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Database();
        $driver->setNewDatabase($configuration);

        static::$driver = $driver;
    }

    /** @throws SessionException */
    public static function useCurrentDatabaseDriver(DB $databaseInstance): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Database();
        $driver->setCurrentDatabase($databaseInstance);

        static::$driver = $driver;
    }

    /** @throws SessionException */
    public static function useNewDatabaseEncryptionDriver(array|Configurator $configuration, string $key, ?string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new DatabaseEncryption();
        $driver->setNewDatabase($configuration);
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /** @throws SessionException */
    public static function useCurrentDatabaseEncryptionDriver(DB $databaseInstance, string $key, ?string $method = null): void // phpcs:ignore
    {
        static::throwExceptionIfHasStarted();

        $driver = new DatabaseEncryption();
        $driver->setCurrentDatabase($databaseInstance);
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /** @throws SessionException */
    public static function useNewRedisDriver(array|string $configuration): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Redis();
        $driver->setNewRedis($configuration);
        $driver->setLifetime(static::getLifetimeForRedis());

        static::$driver = $driver;
    }

    /** @throws SessionException */
    public static function useCurrentRedisDriver(PredisClient $redisInstance): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Redis();
        $driver->setCurrentRedis($redisInstance);
        $driver->setLifetime(static::getLifetimeForRedis());

        static::$driver = $driver;
    }

    /** @throws SessionException */
    public static function useNewRedisEncryptionDriver(array|string $configuration, string $key, ?string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new RedisEncryption();
        $driver->setNewRedis($configuration);
        static::setKeyAndMethod($driver, $key, $method);
        $driver->setLifetime(static::getLifetimeForRedis());

        static::$driver = $driver;
    }

    /** @throws SessionException */
    public static function useCurrentRedisEncryptionDriver(PredisClient $redisInstance, string $key, ?string $method = null): void // phpcs:ignore
    {
        static::throwExceptionIfHasStarted();

        $driver = new RedisEncryption();
        $driver->setCurrentRedis($redisInstance);
        static::setKeyAndMethod($driver, $key, $method);
        $driver->setLifetime(static::getLifetimeForRedis());

        static::$driver = $driver;
    }

    /**
     * @param Encryption $driver (use Encryption trait)
     *
     * @throws SessionException
     */
    private static function setKeyAndMethod(mixed $driver, string $key, ?string $method): void
    {
        $driver->setKey($key);
        if ($method !== null) {
            $driver->setMethod($method);
        }
    }

    /** @throws SessionException */
    public static function useCustomDriver(\SessionHandlerInterface $customDriver): void
    {
        static::throwExceptionIfHasStarted();

        static::$driver = $customDriver;
    }

    public static function getDriver(): \SessionHandlerInterface
    {
        return static::$driver;
    }

    public static function setUserIdForDatabase(int $userId): void
    {
        if (\method_exists(static::$driver, 'setUserId')) {
            static::$driver->setUserId($userId);
        }
    }

    public static function setPrefixForFile(string $prefix): void
    {
        if (\method_exists(static::$driver, 'setPrefix')) {
            static::$driver->setPrefix($prefix);
        }
    }
}
