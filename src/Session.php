<?php

declare(strict_types=1);

namespace Rancoud\Session;

/**
 * Class Session.
 */
class Session extends DriverManager
{
    use ArrayManager;

    protected static bool $hasStarted = false;

    protected static bool $hasChanged = true;

    protected static array $options = [
        'read_and_close'   => true,
        'cookie_httponly'  => '1',
        'use_only_cookies' => '1',
        'use_trans_sid'    => '0',
        'use_strict_mode'  => '1'
    ];

    /** @throws SessionException */
    public static function start(array $options = []): void
    {
        static::throwExceptionIfHasStarted();

        static::populateOptions($options);

        static::setupAndStart();
    }

    /** @throws SessionException */
    protected static function populateOptions(array $options = []): void
    {
        static::validateOptions($options);

        static::setOptions($options);
    }

    /** @throws SessionException */
    protected static function setupAndStart(): void
    {
        if (static::$hasChanged === false) {
            return;
        }

        static::$hasChanged = false;

        static::configureDriver();
        static::setupSessionParameters();
        static::startSession();

        $sessionFlashData = $_SESSION['flash_data'] ?? [];
        foreach ($sessionFlashData as $key => $value) {
            static::$flashData[$key] = $value;
        }

        if (static::$hasStarted === false && !empty(static::$flashData) && static::isReadOnly()) {
            static::setReadWrite();
            static::startSession();
        }
        unset($_SESSION['flash_data']);

        static::$hasStarted = (static::$options['read_and_close'] === false);
    }

    /** @throws SessionException */
    protected static function throwExceptionIfHasStarted(): void
    {
        if (static::hasStarted()) {
            throw new SessionException('Session already started');
        }
    }

    /** @throws SessionException */
    protected static function setupSessionParameters(): void
    {
        \session_name(static::getOption('name'));

        \session_set_save_handler(static::$driver, true);

        \session_save_path(static::getOption('save_path'));

        static::setupCookieParams();
    }

    /** @throws SessionException */
    protected static function startSession(): bool
    {
        static::validateOptions(static::$options);

        static::setupCookieParams();

        return \session_start(static::$options);
    }

    /** @throws SessionException */
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
            'cookie_samesite',
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

        $keys = \array_keys($options);
        foreach ($keys as $key) {
            if (!\in_array($key, $validOptions, true)) {
                throw new SessionException('Incorrect option: ' . $key);
            }
        }
    }

    /** @throws SessionException */
    protected static function setupCookieParams(): void
    {
        // https://www.php.net/manual/fr/function.session-set-cookie-params.php new signature
        \session_set_cookie_params(
            [
                'lifetime' => static::getOption('cookie_lifetime'),
                'path'     => static::getOption('cookie_path'),
                'domain'   => static::getOption('cookie_domain'),
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => static::getOption('cookie_samesite')
            ]
        );
    }

    /** @throws SessionException */
    public static function regenerate(): bool
    {
        static::startSessionIfNotHasStartedForceWrite();

        return \session_regenerate_id(true);
    }

    public static function destroy(): bool
    {
        static::$hasChanged = true;
        \session_unset();

        return \session_destroy();
    }

    public static function commit(): void
    {
        static::$hasStarted = false;
        static::$flashData = [];
        static::$hasChanged = true;

        \session_write_close();
    }

    public static function rollback(): bool
    {
        return \session_reset();
    }

    public static function unsaved(): bool
    {
        static::$hasStarted = false;
        static::$flashData = [];
        static::$hasChanged = true;

        return \session_abort();
    }

    public static function hasStarted(): bool
    {
        return static::$hasStarted;
    }

    public static function getId(): string
    {
        return \session_id();
    }

    public static function setId(string $id): string
    {
        static::$hasChanged = true;

        return \session_id($id);
    }

    /** @throws SessionException */
    public static function gc(): void
    {
        static::startSessionIfNotHasStartedForceWrite();

        \session_gc();
    }

    /** @throws SessionException */
    protected static function startSessionIfNotHasStarted(): void
    {
        if (!static::hasStarted()) {
            static::setupAndStart();
        }
    }

    /** @throws SessionException */
    protected static function startSessionIfNotHasStartedForceWrite(): void
    {
        if (!static::hasStarted()) {
            static::setReadWrite();
            static::setupAndStart();
        }
    }

    public static function setReadOnly(): void
    {
        static::$hasChanged = true;
        static::$options['read_and_close'] = true;
    }

    public static function setReadWrite(): void
    {
        static::$hasChanged = true;
        static::$options['read_and_close'] = false;
    }

    public static function isReadOnly(): bool
    {
        return static::$options['read_and_close'] === true;
    }

    /** @throws SessionException */
    protected static function getLifetimeForRedis(): int
    {
        return (int) static::getOption('cookie_lifetime');
    }

    /**
     * @throws SessionException
     */
    public static function setOption(string $key, $value): void
    {
        static::$hasChanged = true;
        static::validateOptions([$key => $value]);
        static::$options[$key] = $value;
    }

    /** @throws SessionException */
    public static function setOptions(array $options): void
    {
        static::$hasChanged = true;
        static::validateOptions($options);
        static::$options = $options + static::$options;
    }

    /** @throws SessionException */
    public static function getOption(string $key)
    {
        if (\array_key_exists($key, static::$options)) {
            return static::$options[$key];
        }

        static::validateOptions([$key => '']);
        static::$options[$key] = \ini_get('session.' . $key);

        if ($key === 'save_path' && empty(static::$options[$key])) {
            static::$options[$key] = '/tmp';
        }

        return static::$options[$key];
    }
}
