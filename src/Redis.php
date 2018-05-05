<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Predis\Client as Predis;
use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Class Redis.
 */
class Redis implements SessionHandlerInterface, SessionIdInterface, SessionUpdateTimestampHandlerInterface
{
    /** @var Predis */
    protected $redis;

    /** @var int */
    protected $lifetime = 1440;

    /**
     * @param $configuration
     */
    public function setNewRedis($configuration): void
    {
        $this->redis = new Predis($configuration);
    }

    /**
     * @param $redis
     */
    public function setCurrentRedis($redis): void
    {
        $this->redis = $redis;
    }

    /**
     * @param $lifetime
     */
    public function setLifetime($lifetime): void
    {
        $this->lifetime = $lifetime;
    }

    /**
     * @param $savePath
     * @param $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName): bool
    {
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
        return (string) $this->redis->get($sessionId);
    }

    /**
     * @param $sessionId
     * @param $data
     *
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        $this->redis->set($sessionId, $data);
        $this->redis->expireat($sessionId, time() + $this->lifetime);

        return true;
    }

    /**
     * @param $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        $this->redis->del([$sessionId]);

        return true;
    }

    /**
     * @param $lifetime
     *
     * @return bool
     */
    public function gc($lifetime): bool
    {
        return true;
    }

    /**
     * Checks if a session identifier already exists or not.
     *
     * @param string $key
     *
     * @return bool
     */
    public function validateId($key): bool
    {
        return preg_match('/^[a-zA-Z0-9-]{127}+$/', $key) === 1;
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
     * @return string
     */
    public function create_sid(): string
    {
        $string = '';
        $caracters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-';

        $countCaracters = mb_strlen($caracters) - 1;
        for ($i = 0; $i < 127; ++$i) {
            $string .= $caracters[rand(0, $countCaracters)];
        }

        return $string;
    }
}
