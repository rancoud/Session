<?php

declare(strict_types=1);

namespace Rancoud\Session;

class DefaultEncryption extends \SessionHandler
{
    use Encryption;

    /** @throws SessionException */
    public function read(string $id): string
    {
        $encryptedData = parent::read($id);

        return $this->decrypt($encryptedData);
    }

    /** @throws SessionException */
    public function write(string $id, string $data): bool
    {
        $cryptedData = $this->encrypt($data);

        return parent::write($id, $cryptedData);
    }
}
