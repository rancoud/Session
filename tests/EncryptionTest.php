<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\SessionException;

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
            static::assertEquals($dataToEncrypt, $finalData, $method . ' fail!');
        }
    }

    public function testExceptionMethod()
    {
        static::expectException(SessionException::class);
        static::expectExceptionMessage('Method unknowed: method');
        
        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setMethod('method');
    }

    public function testExceptionEmptyKey()
    {
        static::expectException(SessionException::class);
        static::expectExceptionMessage('Key has to be a non-empty string');
        
        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $dataToEncrypt = 'this is something to encrypt';
        $encryptionTrait->encrypt($dataToEncrypt);
    }
}
