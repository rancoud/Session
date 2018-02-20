<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use Exception;
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

    public function testAllEncryptionMethods()
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

    public function testExceptionMethod()
    {
        static::expectException(Exception::class);
        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setMethod('method');
    }

    public function testExceptionEmptyKey()
    {
        static::expectException(Exception::class);
        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $dataToEncrypt = 'this is something to encrypt';
        $encryptionTrait->encrypt($dataToEncrypt);
    }
}
