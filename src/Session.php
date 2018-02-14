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
    /** @var SessionHandlerInterface */
    protected $driver = null;
    /** @var string */
    protected $name = null;
    /** @var string */
    protected $folder = null;
    /** @var string */
    protected $cookieDomain = null;
    /** @var string */
    protected $lifetime = null;

    /**
     * Session constructor.
     *
     * @param array $configuration
     *
     * @throws Exception
     */
    public function __construct(array $configuration)
    {
        $this->validateConfiguration($configuration);
        $this->applyConfigurationToAttributes($configuration);
        $this->configureDriver();
        $this->setupSessionParameters();
        $this->startSession();
        $this->driver->gc($this->lifetime);
    }

    /**
     * @param array $configuration
     *
     * @throws Exception
     */
    protected function validateConfiguration(array $configuration): void
    {
        $props = ['driver', 'name', 'folder', 'cookie_domain', 'lifetime'];
        foreach ($props as $prop) {
            if (!array_key_exists($prop, $configuration)) {
                throw new Exception('Property "' . $prop . '" missing in Session Configuration');
            }
        }
    }

    /**
     * @param array $configuration
     */
    protected function applyConfigurationToAttributes(array $configuration): void
    {
        $this->driver = $configuration['driver'];
        $this->name = $configuration['name'];
        $this->folder = $configuration['folder'];
        $this->cookieDomain = $configuration['cookie_domain'];
        $this->lifetime = $configuration['lifetime'];
    }

    protected function configureDriver(): void
    {
        switch ($this->driver) {
            case 'file':
                $this->driver = 'Rancoud\Session\File';
                break;
            default:
                $this->driver = new SessionHandler();
        }

        $this->driver = new $this->driver();
    }

    protected function setupSessionParameters(): void
    {
        session_name($this->name);

        session_set_save_handler($this->driver);

        register_shutdown_function('session_write_close');
    }

    /**
     * @return bool
     */
    protected function startSession(): bool
    {
        ini_set('session.cookie_domain', $this->cookieDomain);
        ini_set('session.cookie_httponly', '1');
        ini_set('session.save_path', $this->folder);
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.url_rewriter.tags', '');

        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            $this->lifetime,
            $cookieParams['path'],
            $cookieParams['domain'],
            isset($_SERVER['HTTPS']),
            true
        );

        return session_start();
    }

    public function regenerate(): void
    {
        session_name($this->name);
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * @param $key
     * @param $value
     */
    public function set($key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function get($key): mixed
    {
        return ($this->has($key)) ? $_SESSION[$key] : null;
    }

    /**
     * @param $key
     */
    public function remove($key): void
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }
}
