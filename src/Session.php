<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Exception;

/**
 * Class Session.
 */
class Session extends DriverManager
{
    use ArrayManager;

    /** @var bool */
    protected static $hasStarted = false;

    /** @var array */
    protected static $options = [
        'read_and_close'   => true,
        'cookie_httponly'  => '1',
        'use_only_cookies' => '1',
        'use_trans_sid'    => '0',
        'use_strict_mode'  => '1'
    ];

    /**
     * @param array $options
     *
     * @throws Exception
     */
    public static function start(array $options = []): void
    {
        static::throwExceptionIfHasStarted();

        static::populateOptions($options);

        static::setupAndStart();
    }

    /**
     * @param array $options
     *
     * @throws Exception
     */
    protected static function populateOptions(array $options = []): void
    {
        self::validateOptions($options);

        static::setOptions($options);
    }

    /**
     * @throws Exception
     */
    protected static function setupAndStart(): void
    {
        static::configureDriver();
        static::setupSessionParameters();
        static::startSession();

        static::$hasStarted = true;
        if (static::$options['read_and_close']) {
            static::$hasStarted = false;
        } else {
            static::restoreFlashData();
        }
    }

    /**
     * @throws SessionException
     */
    protected static function throwExceptionIfHasStarted(): void
    {
        if (static::hasStarted()) {
            throw new SessionException('Session already started');
        }
    }

    /**
     * @throws SessionException
     */
    protected static function setupSessionParameters(): void
    {
        session_name(static::getOption('name'));

        session_set_save_handler(static::$driver);

        session_save_path(static::getOption('save_path'));

        static::setupCookieParams();

        register_shutdown_function('session_write_close');
    }

    /**
     * @throws SessionException
     *
     * @return bool
     */
    protected static function startSession(): bool
    {
        static::validateOptions(static::$options);

        static::setupCookieParams();

        return session_start(static::$options);
    }

    /**
     * @param array $options
     *
     * @throws SessionException
     */
    protected static function validateOptions(array $options = []): void
    {
        if (empty($options)) {
            return;
        }

        $validOptions = [
            'save_path',
            'name',
            'save_handler',
            'auto_start',
            'gc_probability',
            'gc_divisor',
            'gc_maxlifetime',
            'serialize_handler',
            'cookie_lifetime',
            'cookie_path',
            'cookie_domain',
            'cookie_secure',
            'cookie_httponly',
            'use_strict_mode',
            'use_cookies',
            'use_only_cookies',
            'referer_check',
            'cache_limiter',
            'cache_expire',
            'use_trans_sid',
            'trans_sid_tags',
            'trans_sid_hosts',
            'sid_length',
            'sid_bits_per_character',
            'upload_progress.enabled',
            'upload_progress.cleanup',
            'upload_progress.prefix',
            'upload_progress.name',
            'upload_progress.freq',
            'upload_progress.min_freq',
            'lazy_write',
            'read_and_close'
        ];

        $keys = array_keys($options);
        foreach ($keys as $key) {
            if (!\in_array($key, $validOptions, true)) {
                throw new SessionException('Incorrect option: ' . $key);
            }
        }
    }

    /**
     * @throws SessionException
     */
    protected static function setupCookieParams(): void
    {
        session_set_cookie_params(
            static::getOption('cookie_lifetime'),
            static::getOption('cookie_path'),
            static::getOption('cookie_domain'),
            isset($_SERVER['HTTPS']),
            true
        );
    }

    /**
     * @throws Exception
     */
    public static function regenerate(): bool
    {
        static::startSessionIfNotHasStartedForceWrite();

        return session_regenerate_id(true);
    }

    /**
     * @return bool
     */
    public static function destroy(): bool
    {
        session_unset();

        return session_destroy();
    }

    public static function commit(): void
    {
        static::$hasStarted = false;

        session_commit();
    }

    /**
     * @return bool
     */
    public static function rollback(): bool
    {
        return session_reset();
    }

    /**
     * @return bool
     */
    public static function unsaved(): bool
    {
        static::$hasStarted = false;

        return session_abort();
    }

    /**
     * @return bool
     */
    public static function hasStarted(): bool
    {
        return static::$hasStarted;
    }

    /**
     * @return string
     */
    public static function getId(): string
    {
        return session_id();
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public static function setId(string $id): string
    {
        return session_id($id);
    }

    /**
     * @throws Exception
     */
    public static function gc(): void
    {
        static::startSessionIfNotHasStartedForceWrite();

        session_gc();
    }

    /**
     * @throws Exception
     */
    protected static function startSessionIfNotHasStarted(): void
    {
        if (!static::hasStarted()) {
            static::setupAndStart();
        }
    }

    /**
     * @throws Exception
     */
    protected static function startSessionIfNotHasStartedForceWrite(): void
    {
        if (!static::hasStarted()) {
            static::$options['read_and_close'] = false;
            static::setupAndStart();
        }
    }

    public static function setReadOnly(): void
    {
        static::$options['read_and_close'] = true;
    }

    public static function setReadWrite(): void
    {
        static::$options['read_and_close'] = false;
    }

    /**
     * @throws SessionException
     *
     * @return mixed
     */
    protected static function getLifetimeForRedis()
    {
        return static::getOption('cookie_lifetime');
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws SessionException
     */
    public static function setOption(string $key, $value): void
    {
        static::validateOptions([$key => $value]);
        static::$options[$key] = $value;
    }

    /**
     * @param array $options
     *
     * @throws SessionException
     */
    public static function setOptions(array $options): void
    {
        static::validateOptions($options);
        static::$options = $options + static::$options;
    }

    /**
     * @param string $key
     *
     * @throws SessionException
     *
     * @return mixed
     */
    public static function getOption(string $key)
    {
        if (array_key_exists($key, static::$options)) {
            return static::$options[$key];
        }

        static::validateOptions([$key => '']);
        static::$options[$key] = ini_get('session.' . $key);

        if ($key === 'save_path' && empty(static::$options[$key])) {
            static::$options[$key] = '/tmp';
        }

        return static::$options[$key];
    }
}
