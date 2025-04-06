<?php

declare(strict_types=1);

namespace Rancoud\Session;

/**
 * Class FileEncryption.
 */
class FileEncryption extends File
{
    use Encryption;

    /**
     * @param string $id
     *
     * @throws SessionException
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
     */
    public function write($id, $data): bool
    {
        $cryptedData = $this->encrypt($data);

        return parent::write($id, $cryptedData);
    }
}
