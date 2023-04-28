<?php

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
        foreach ($methods as $method) {
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

    public function testExceptionMethodForced(): void
    {
        $forbiddenMethods = [
            'aes-128-cbc-hmac-sha1',
            'aes-256-cbc-hmac-sha1'
        ];

        $encryptionTrait = new class() {
            use \Rancoud\Session\Encryption;

            public function setMethod(string $method): void
            {
                $this->method = $method;
            }
        };

        $dataToEncrypt = 'this is something to encrypt';

        $encryptionTrait->setKey('my key');

        if (\PHP_MAJOR_VERSION >= 8 && \PHP_MINOR_VERSION >= 1) {
            \array_push(
                $forbiddenMethods,
                'aes-128-cbc-cts',
                'aes-128-cbc-hmac-sha256',
                'aes-128-siv',
                'aes-192-cbc-cts',
                'aes-192-siv',
                'aes-256-cbc-cts',
                'aes-256-cbc-hmac-sha256',
                'aes-256-siv',
                'camellia-128-cbc-cts',
                'camellia-192-cbc-cts',
                'camellia-256-cbc-cts',
                'null',
                '0.3.4401.5.3.1.9.1',
                '0.3.4401.5.3.1.9.21',
                '0.3.4401.5.3.1.9.41',
                '1.2.410.200046.1.1.1',
                '1.2.410.200046.1.1.11',
                '1.2.410.200046.1.1.34',
                '1.2.410.200046.1.1.35',
                '1.2.410.200046.1.1.36',
                '1.2.410.200046.1.1.37',
                '1.2.410.200046.1.1.38',
                '1.2.410.200046.1.1.39',
                '1.2.410.200046.1.1.6',
                '1.2.840.113549.1.9.16.3.6',
                '1.3.14.3.2.17',
                '2.16.840.1.101.3.4.1.1',
                '2.16.840.1.101.3.4.1.21',
                '2.16.840.1.101.3.4.1.25',
                '2.16.840.1.101.3.4.1.26',
                '2.16.840.1.101.3.4.1.27',
                '2.16.840.1.101.3.4.1.41',
                '2.16.840.1.101.3.4.1.45',
                '2.16.840.1.101.3.4.1.46',
                '2.16.840.1.101.3.4.1.47',
                '2.16.840.1.101.3.4.1.5',
                '2.16.840.1.101.3.4.1.6',
                '2.16.840.1.101.3.4.1.7'
            );
        }

        if (\PHP_MAJOR_VERSION >= 8 && \PHP_MINOR_VERSION >= 2) {
            $forbiddenMethods[] = 'chacha20-poly1305';
        }

        $countInvalidMethods = \count($forbiddenMethods);
        foreach ($forbiddenMethods as $method) {
            try {
                $encryptionTrait->setMethod($method);
                $finalData = $encryptionTrait->encrypt($dataToEncrypt);
                static::assertNotSame($dataToEncrypt, $finalData);
                --$countInvalidMethods;
            } catch (\Exception $e) {
                static::assertThat($e->getMessage(), static::logicalOr(
                    static::equalTo('IV generation failed'),
                    static::equalTo('openssl_encrypt(): A tag should be provided when using AEAD mode')
                ));
                --$countInvalidMethods;
            }
        }

        static::assertSame(0, $countInvalidMethods);
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
