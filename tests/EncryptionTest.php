<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;

/**
 * Class EncryptedTest.
 */
class EncryptionTest extends TestCase
{
    public function testDefaultEncryption()
    {
        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');

        $dataToEncrypt = 'this is something to encrypt';

        $encryptionTrait->setKey('my key');
        $encryptedData = $encryptionTrait->encrypt($dataToEncrypt);

        $finalData = $encryptionTrait->decrypt($encryptedData);
        static::assertEquals($dataToEncrypt, $finalData);
    }

    public function testAllEncryption()
    {
        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');

        $dataToEncrypt = 'this is something to encrypt';

        $encryptionTrait->setKey('my key');
        $methods = $encryptionTrait->getAvailableMethods();
        foreach ($methods as $method) {
            $encryptionTrait->setMethod($method);
            $encryptedData = $encryptionTrait->encrypt($dataToEncrypt);

            $finalData = $encryptionTrait->decrypt($encryptedData);
            static::assertEquals($dataToEncrypt, $finalData);
        }
    }
}
