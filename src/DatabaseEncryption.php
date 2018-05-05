<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Rancoud\Database\DatabaseException;

/**
 * Class DatabaseEncryption.
 */
class DatabaseEncryption extends Database
{
    use Encryption;

    /**
     * @param string $sessionId
     *
     * @return string
     * @throws SessionException
     * @throws DatabaseException
     */
    public function read($sessionId): string
    {
        $encryptedData = parent::read($sessionId);

        return $this->decrypt($encryptedData);
    }

    /**
     * @param string $sessionId
     * @param string $data
     *
     * @return bool
     * @throws DatabaseException
     * @throws SessionException
     */
    public function write($sessionId, $data): bool
    {
        $cryptedData = $this->encrypt($data);

        return parent::write($sessionId, $cryptedData);
    }
}
