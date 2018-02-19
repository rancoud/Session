<?php

declare(strict_types=1);

namespace Rancoud\Session;

use SessionHandlerInterface;

/**
 * Class Redis.
 */
class Redis implements SessionHandlerInterface
{
    protected $redis;

    public function setNewRedis($configuration)
    {
        $this->redis = new Predis\Client($configuration);
    }

    public function setCurrentRedis($redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param $savePath
     * @param $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName): bool
    {
        $this->savePath = $savePath;

        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777);
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
     * @param $sessionId
     *
     * @return string
     */
    public function read($sessionId): string
    {
        $filename = $this->savePath . '/sess_' . $sessionId;
        if (file_exists($filename)) {
            return (string) file_get_contents($filename);
        }

        return '';
    }

    /**
     * @param $sessionId
     * @param $data
     *
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        return file_put_contents($this->savePath . '/sess_' . $sessionId, $data) === false ? false : true;
    }

    /**
     * @param $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        $filename = $this->savePath . '/sess_' . $sessionId;
        if (file_exists($this->savePath . '/sess_' . $sessionId)) {
            unlink($filename);
        }

        return true;
    }

    /**
     * @param $lifetime
     *
     * @return bool
     */
    public function gc($lifetime): bool
    {
        foreach (glob($this->savePath . '/sess_*') as $file) {
            if (filemtime($file) + $lifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
    }
}
