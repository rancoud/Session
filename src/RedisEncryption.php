<?php

declare(strict_types=1);

namespace Rancoud\Session;

/**
 * Class RedisEncryption.
 */
class RedisEncryption extends Redis
{
    use Encryption;

    /**
     * @param $sessionId
     *
     * @throws SessionException
     *
     * @return string
     */
    public function read($sessionId): string
    {
        $encryptedData = parent::read($sessionId);

        return $this->decrypt($encryptedData);
    }

    /**
     * @param $sessionId
     * @param $data
     *
     * @throws SessionException
     *
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        $cryptedData = $this->encrypt($data);

        return parent::write($sessionId, $cryptedData);
    }
}
