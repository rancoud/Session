<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Exception;
use SessionHandler;
use SessionHandlerInterface;

/**
 * Class Session.
 */
class Session
{
    protected static $hasStarted = false;
    /** @var SessionHandlerInterface */
    protected static $driver = null;
    /** @var string */
    protected static $name = null;
    /** @var string */
    protected static $savePath = null;
    /** @var string */
    protected static $cookieDomain = null;
    /** @var string */
    protected static $lifetime = 1440;

    /**
     * @throws \Exception
     */
    public static function start(): void
    {
        static::throwExceptionIfHasStarted();

        static::configureDriver();
        static::setupSessionParameters();
        static::startSession();
        static::$driver->gc(static::$lifetime);

        static::$hasStarted = true;
    }

    /**
     * @throws \Exception
     */
    protected static function throwExceptionIfHasStarted(): void
    {
        if (static::$hasStarted) {
            throw new Exception('Session already started');
        }
    }

    /**
     * @throws \Exception
     */
    protected static function configureDriver(): void
    {
        if (empty(static::$driver)) {
            static::useDefaultDriver();
        }
    }

    /**
     * @throws \Exception
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
     * @throws \Exception
     */
    public static function useDefaultEncryptionDriver(string $key, string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new DefaultEncryption();
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /**
     * @throws \Exception
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
     * @throws \Exception
     */
    public static function useFileEncryptionDriver(string $key, string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new FileEncryption();
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /**
     * @param $configuration
     *
     * @throws Exception
     */
    public static function useNewDatabaseDriver($configuration): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Database();
        $driver->setNewDatabase($configuration);

        static::$driver = $driver;
    }

    /**
     * @param $database
     *
     * @throws Exception
     */
    public static function useCurrentDatabaseDriver($database): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Database();
        $driver->setCurrentDatabase($database);

        static::$driver = $driver;
    }

    /**
     * @param string $key
     * @param        $configuration
     * @param string $method
     *
     * @throws Exception
     */
    public static function useNewDatabaseEncryptionDriver($configuration, string $key, string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new DatabaseEncryption();
        $driver->setNewDatabase($configuration);
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /**
     * @param string $key
     * @param        $database
     * @param string $method
     *
     * @throws Exception
     */
    public static function useCurrentDatabaseEncryptionDriver($database, string $key, string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new DatabaseEncryption();
        $driver->setCurrentDatabase($database);
        static::setKeyAndMethod($driver, $key, $method);

        static::$driver = $driver;
    }

    /**
     * @param $configuration
     *
     * @throws Exception
     */
    public static function useNewRedisDriver($configuration): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Redis();
        $driver->setNewRedis($configuration);
        $driver->setLifetime(static::$lifetime);

        static::$driver = $driver;
    }

    /**
     * @param $redis
     *
     * @throws Exception
     */
    public static function useCurrentRedisDriver($redis): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new Redis();
        $driver->setCurrentRedis($redis);
        $driver->setLifetime(static::$lifetime);

        static::$driver = $driver;
    }

    /**
     * @param string $key
     * @param        $configuration
     * @param string $method
     *
     * @throws Exception
     */
    public static function useNewRedisEncryptionDriver($configuration, string $key, string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new RedisEncryption();
        $driver->setNewRedis($configuration);
        static::setKeyAndMethod($driver, $key, $method);
        $driver->setLifetime(static::$lifetime);

        static::$driver = $driver;
    }

    /**
     * @param string $key
     * @param        $redis
     * @param string $method
     *
     * @throws Exception
     */
    public static function useCurrentRedisEncryptionDriver($redis, string $key, string $method = null): void
    {
        static::throwExceptionIfHasStarted();

        $driver = new RedisEncryption();
        $driver->setCurrentRedis($redis);
        static::setKeyAndMethod($driver, $key, $method);
        $driver->setLifetime(static::$lifetime);

        static::$driver = $driver;
    }

    /**
     * @param \Rancoud\Session\Encryption $driver
     * @param                             $key
     * @param                             $method
     *
     * @throws \Exception
     */
    private static function setKeyAndMethod($driver, $key, $method): void
    {
        $driver->setKey($key);
        if ($method !== null) {
            $driver->setMethod($method);
        }
    }

    /**
     * @param \SessionHandlerInterface $customDriver
     *
     * @throws \Exception
     */
    public static function useCustomDriver(SessionHandlerInterface $customDriver): void
    {
        static::throwExceptionIfHasStarted();

        static::$driver = $customDriver;
    }

    /**
     * @param string $name
     *
     * @throws \Exception
     */
    public static function setName(string $name): void
    {
        static::throwExceptionIfHasStarted();

        static::$name = $name;
    }

    /**
     * @param string $savePath
     *
     * @throws \Exception
     */
    public static function setSavePath(string $savePath): void
    {
        static::throwExceptionIfHasStarted();

        static::$savePath = $savePath;
    }

    /**
     * @param string $cookieDomain
     *
     * @throws Exception
     */
    public static function setCookieDomain(string $cookieDomain): void
    {
        static::throwExceptionIfHasStarted();

        static::$cookieDomain = $cookieDomain;
    }

    /**
     * @param int $lifetime
     *
     * @throws \Exception
     */
    public static function setLifetime(int $lifetime): void
    {
        static::throwExceptionIfHasStarted();

        static::$lifetime = $lifetime;
    }

    protected static function setupSessionParameters(): void
    {
        if (!empty(static::$name)) {
            session_name(static::$name);
        }

        session_set_save_handler(static::$driver);

        register_shutdown_function('session_write_close');
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    protected static function startSession($options = []): bool
    {
        static::setupIniSession();

        static::setupCookieParams();

        return session_start($options);
    }

    protected static function setupIniSession(): void
    {
        if (!empty(static::$savePath)) {
            ini_set('session.save_path', static::$savePath);
            session_save_path(static::$savePath);
        } elseif (empty(ini_get('session.save_path'))) {
            static::$savePath = '/tmp';
            ini_set('session.save_path', static::$savePath);
            session_save_path(static::$savePath);
        }

        if (!empty(static::$cookieDomain)) {
            ini_set('session.cookie_domain', static::$cookieDomain);
        }

        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
    }

    protected static function setupCookieParams(): void
    {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            static::$lifetime,
            $cookieParams['path'],
            $cookieParams['domain'],
            isset($_SERVER['HTTPS']),
            true
        );
    }

    public static function regenerate(): void
    {
        session_name(static::$name);
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * @return bool
     */
    public static function abort(): bool
    {
        return session_abort();
    }

    /**
     * @param $key
     * @param $value
     *
     * @throws \Exception
     */
    public static function set($key, $value): void
    {
        static::startSessionIfNotHasStarted();

        $_SESSION[$key] = $value;
    }

    /**
     * @param $key
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function has($key): bool
    {
        static::startSessionIfNotHasStarted();

        return array_key_exists($key, $_SESSION);
    }

    /**
     * @param $key
     * @param $value
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function hasKeyAndValue($key, $value): bool
    {
        static::startSessionIfNotHasStarted();

        return array_key_exists($key, $_SESSION) && $_SESSION[$key] === $value;
    }

    /**
     * @param $key
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public static function get($key)
    {
        static::startSessionIfNotHasStarted();

        return (static::has($key)) ? $_SESSION[$key] : null;
    }

    /**
     * @param $key
     *
     * @throws \Exception
     */
    public static function remove($key): void
    {
        static::startSessionIfNotHasStarted();

        if (static::has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * @throws \Exception
     */
    protected static function startSessionIfNotHasStarted(): void
    {
        if (static::$hasStarted === false) {
            static::start();
        }
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
        static::$driver->setUserId($userId);
    }

    /**
     * @param string $prefix
     */
    public static function setPrefixForFile(string $prefix): void
    {
        static::$driver->setPrefix($prefix);
    }
}
