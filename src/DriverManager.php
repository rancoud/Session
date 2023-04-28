<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Predis\Client as PredisClient;
use Rancoud\Database\Configurator;
use Rancoud\Database\Database as DB;
use SessionHandler;
use SessionHandlerInterface;

/**
 * Class DriverManager.
 */
abstract class DriverManager
{
    protected static ?SessionHandlerInterface $driver = null;

    /** @throws SessionException */
    abstract protected static function throwExceptionIfHasStarted();

    abstract protected static function getLifetimeForRedis();

    /**
     * @throws SessionException
     */
    protected static function configureDriver(): void
    {
        if (empty(static::$driver)) {
            static::useDefaultDriver();
        }
    }

    /**
     * @throws SessionException
     */
    public static function useDefaultDriver(): void
    {
        static::throwExceptionIfHasStarted();

        static::$driver = new SessionHandler();
    }

    /**
     * @param string      $key
     * @param string|null $method
     *
     * @throws SessionException
     */
    public static function useDefaultEncryptionDriver(string $key, string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new DefaultEncryption();
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /**
     * @throws SessionException
     */
    public static function useFileDriver(): void
    {
        static::throwExceptionIfHasStarted();

        static::$driver = new File();
    }

    /**
     * @param string      $key
     * @param string|null $method
     *
     * @throws SessionException
     */
    public static function useFileEncryptionDriver(string $key, string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new FileEncryption();
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /**
     * @param Configurator|array $configuration
     *
     * @throws SessionException
     */
    public static function useNewDatabaseDriver($configuration): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Database();
        $driver->setNewDatabase($configuration);

        static::$driver = $driver;
    }

    /**
     * @param DB $databaseInstance
     *
     * @throws SessionException
     */
    public static function useCurrentDatabaseDriver(DB $databaseInstance): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Database();
        $driver->setCurrentDatabase($databaseInstance);

        static::$driver = $driver;
    }

    /**
     * @param Configurator|array $configuration
     * @param string             $key
     * @param string|null        $method
     *
     * @throws SessionException
     */
    public static function useNewDatabaseEncryptionDriver($configuration, string $key, ?string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new DatabaseEncryption();
        $driver->setNewDatabase($configuration);
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /**
     * @param DB          $databaseInstance
     * @param string      $key
     * @param string|null $method
     *
     * @throws SessionException
     */
    public static function useCurrentDatabaseEncryptionDriver(DB $databaseInstance, string $key, ?string $method = null): void // phpcs:ignore
    {
        static::throwExceptionIfHasStarted();

        $driver = new DatabaseEncryption();
        $driver->setCurrentDatabase($databaseInstance);
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /**
     * @param array|string $configuration
     *
     * @throws SessionException
     */
    public static function useNewRedisDriver($configuration): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Redis();
        $driver->setNewRedis($configuration);
        $driver->setLifetime(static::getLifetimeForRedis());

        static::$driver = $driver;
    }

    /**
     * @param PredisClient $redisInstance
     *
     * @throws SessionException
     */
    public static function useCurrentRedisDriver(PredisClient $redisInstance): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Redis();
        $driver->setCurrentRedis($redisInstance);
        $driver->setLifetime(static::getLifetimeForRedis());

        static::$driver = $driver;
    }

    /**
     * @param array|string $configuration
     * @param string       $key
     * @param string|null  $method
     *
     * @throws SessionException
     */
    public static function useNewRedisEncryptionDriver($configuration, string $key, string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new RedisEncryption();
        $driver->setNewRedis($configuration);
        static::setKeyAndMethod($driver, $key, $method);
        $driver->setLifetime(static::getLifetimeForRedis());

        static::$driver = $driver;
    }

    /**
     * @param PredisClient $redisInstance
     * @param string       $key
     * @param string|null  $method
     *
     * @throws SessionException
     */
    public static function useCurrentRedisEncryptionDriver(PredisClient $redisInstance, string $key, string $method = null): void // phpcs:ignore
    {
        static::throwExceptionIfHasStarted();

        $driver = new RedisEncryption();
        $driver->setCurrentRedis($redisInstance);
        static::setKeyAndMethod($driver, $key, $method);
        $driver->setLifetime(static::getLifetimeForRedis());

        static::$driver = $driver;
    }

    /**
     * @param Encryption  $driver (use Encryption trait)
     * @param string      $key
     * @param string|null $method
     *
     * @throws SessionException
     *
     * @noinspection PhpMissingParamTypeInspection
     */
    private static function setKeyAndMethod($driver, string $key, ?string $method): void
    {
        $driver->setKey($key);
        if ($method !== null) {
            $driver->setMethod($method);
        }
    }

    /**
     * @param SessionHandlerInterface $customDriver
     *
     * @throws SessionException
     */
    public static function useCustomDriver(SessionHandlerInterface $customDriver): void
    {
        static::throwExceptionIfHasStarted();

        static::$driver = $customDriver;
    }

    /**
     * @return SessionHandlerInterface
     */
    public static function getDriver(): SessionHandlerInterface
    {
        return static::$driver;
    }

    /**
     * @param int $userId
     */
    public static function setUserIdForDatabase(int $userId): void
    {
        if (\method_exists(static::$driver, 'setUserId')) {
            static::$driver->setUserId($userId);
        }
    }

    /**
     * @param string $prefix
     */
    public static function setPrefixForFile(string $prefix): void
    {
        if (\method_exists(static::$driver, 'setPrefix')) {
            static::$driver->setPrefix($prefix);
        }
    }
}
