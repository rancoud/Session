<?php

declare(strict_types=1);

namespace Rancoud\Session;

use SessionHandler;

/**
 * Class DefaultEncryption.
 */
class DefaultEncryption extends SessionHandler
{
    use Encryption;

    /**
     * @param string $id
     *
     * @throws SessionException
     *
     * @return string
     */
    public function read($id): string
    {
        $encryptedData = parent::read($id);

        return $this->decrypt($encryptedData);
    }

    /**
     * @param string $id
     * @param string $data
     *
     * @throws SessionException
     *
     * @return bool
     */
    public function write($id, $data): bool
    {
        $cryptedData = $this->encrypt($data);

        return parent::write($id, $cryptedData);
    }
}
