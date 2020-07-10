<?php

declare(strict_types=1);

namespace Rancoud\Session;

use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Class File.
 */
class File implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    /** @var string|null */
    protected ?string $savePath = null;

    /** @var string */
    protected string $prefix = 'sess_';

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName): bool
    {
        $this->savePath = $savePath;

        if (!\is_dir($this->savePath)) {
            \mkdir($this->savePath, 0777);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return string
     */
    public function read($sessionId): string
    {
        $filename = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . $sessionId;
        if (\file_exists($filename)) {
            return (string) \file_get_contents($filename);
        }

        return '';
    }

    /**
     * @param string $sessionId
     * @param string $data
     *
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        $filename = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . $sessionId;

        return \file_put_contents($filename, $data) === false ? false : true;
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        $filename = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . $sessionId;
        if (\file_exists($filename)) {
            \unlink($filename);
        }

        return true;
    }

    /**
     * @param int $lifetime
     *
     * @return bool
     */
    public function gc($lifetime): bool
    {
        $pattern = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . '*';
        foreach (\glob($pattern) as $file) {
            if (\filemtime($file) + $lifetime < \time() && \file_exists($file)) {
                \unlink($file);
            }
        }

        return true;
    }

    /**
     * Checks format and id exists, if not session_id will be regenerate.
     *
     * @param string $key
     *
     * @return bool
     */
    public function validateId($key): bool
    {
        if (\preg_match('/^[a-zA-Z0-9-]{127}+$/', $key) !== 1) {
            return false;
        }

        $filename = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . $key;

        return \file_exists($filename);
    }

    /**
     * Updates the timestamp of a session when its data didn't change.
     *
     * @param string $sessionId
     * @param string $sessionData
     *
     * @return bool
     */
    public function updateTimestamp($sessionId, $sessionData): bool
    {
        return $this->write($sessionId, $sessionData);
    }

    /**
     * @throws \Exception
     *
     * @return string
     */
    public function create_sid(): string
    {
        $string = '';
        $caracters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-';

        $countCaracters = 62;
        for ($i = 0; $i < 127; ++$i) {
            $string .= $caracters[\random_int(0, $countCaracters)];
        }

        $filename = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . $string;
        if (\file_exists($filename)) {
            return $this->create_sid();
        }

        return $string;
    }
}
