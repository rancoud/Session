<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Predis\Client as Predis;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Class Redis.
 */
class Redis implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    protected Predis $redis;

    protected int $lifetime = 1440;

    protected int $lengthSessionID = 127;

    /**
     * @param string|array $configuration
     */
    public function setNewRedis($configuration): void
    {
        $this->redis = new Predis($configuration);
    }

    /**
     * @param Predis $redis
     */
    public function setCurrentRedis(Predis $redis): void
    {
        $this->redis = $redis;
    }

    /**
     * @param int $lifetime
     */
    public function setLifetime(int $lifetime): void
    {
        $this->lifetime = $lifetime;
    }

    /**
     * @param int $length
     *
     * @throws SessionException
     */
    public function setLengthSessionID(int $length): void
    {
        if ($length < 32) {
            throw new SessionException('could not set length session ID below 32');
        }

        $this->lengthSessionID = $length;
    }

    public function getLengthSessionID(): int
    {
        return $this->lengthSessionID;
    }

    /**
     * @param string $path
     * @param string $name
     *
     * @return bool
     */
    public function open($path, $name): bool
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
     * @param string $id
     *
     * @return string
     */
    public function read($id): string
    {
        return (string) $this->redis->get($id);
    }

    /**
     * @param string $id
     * @param string $data
     *
     * @return bool
     */
    public function write($id, $data): bool
    {
        $this->redis->set($id, $data);
        $this->redis->expireat($id, \time() + $this->lifetime);

        return true;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function destroy($id): bool
    {
        $this->redis->del([$id]);

        return true;
    }

    /**
     * @param int $max_lifetime
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function gc($max_lifetime): bool
    {
        return true;
    }

    /**
     * Checks format and id exists, if not session_id will be regenerate.
     *
     * @param string $id
     *
     * @return bool
     */
    public function validateId($id): bool
    {
        if (\preg_match('/^[a-zA-Z0-9-]{' . $this->lengthSessionID . '}+$/', $id) !== 1) {
            return false;
        }

        $exist = $this->redis->exists($id);

        return $exist === 1;
    }

    /**
     * Updates the timestamp of a session when its data didn't change.
     *
     * @param string $id
     * @param string $data
     *
     * @return bool
     */
    public function updateTimestamp($id, $data): bool
    {
        return $this->write($id, $data);
    }

    /**
     * @throws SessionException
     *
     * @return string
     * @noinspection PhpMethodNamingConventionInspection
     */
    public function create_sid(): string
    {
        $string = '';
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-';

        try {
            $countCharacters = 62;
            for ($i = 0; $i < $this->lengthSessionID; ++$i) {
                $string .= $characters[\random_int(0, $countCharacters)];
            }
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /* If an appropriate source of randomness cannot be found, an Exception will be thrown.
             * The list of randomness: https://www.php.net/manual/en/function.random-int.php
             */
            throw new SessionException('could not create sid: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
            // @codeCoverageIgnoreEnd
        }

        $exist = $this->redis->exists($string);
        if ($exist !== 0) {
            // @codeCoverageIgnoreStart
            /* Could not reach this statement without mocking the function
             */
            return $this->create_sid();
            // @codeCoverageIgnoreEnd
        }

        return $string;
    }
}
