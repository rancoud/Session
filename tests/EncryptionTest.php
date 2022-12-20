<?php

/** @noinspection ForgottenDebugOutputInspection */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\SessionException;

/**
 * Class EncryptedTest.
 */
class EncryptionTest extends TestCase
{
    public function testDefaultEncryption(): void
    {
        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');

        $dataToEncrypt = 'this is something to encrypt';

        $encryptionTrait->setKey('my key');
        $encryptedData = $encryptionTrait->encrypt($dataToEncrypt);

        $finalData = $encryptionTrait->decrypt($encryptedData);
        static::assertSame($dataToEncrypt, $finalData);
    }

    public function testAllEncryptionMethods(): void
    {
        $failedMethods = [];

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');

        $dataToEncrypt = 'this is something to encrypt';

        $encryptionTrait->setKey('my key');
        $methods = $encryptionTrait->getAvailableMethods();
        var_dump($methods);
        foreach ($methods as $method) {
            echo 'Method: ' . $method . "\n";
            try {
                $encryptionTrait->setMethod($method);
                $encryptedData = $encryptionTrait->encrypt($dataToEncrypt);

                $finalData = $encryptionTrait->decrypt($encryptedData);
                static::assertSame($dataToEncrypt, $finalData, $method . ' fail!');
            } catch (\Exception $e) {
                $failedMethods[] = $method;
                continue;
            }
        }

        if (\count($failedMethods) > 0) {
            static::fail('Methods ' . \implode(', ', $failedMethods) . ' fail!');
        }
    }

    public function testExceptionMethod(): void
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('Unknown method: method');

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setMethod('method');
    }

    public function testExceptionEmptyKey(): void
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('Key has to be a non-empty string');

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $dataToEncrypt = 'this is something to encrypt';
        $encryptionTrait->encrypt($dataToEncrypt);
    }
}
