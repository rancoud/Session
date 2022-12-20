<?php

declare(strict_types=1);

namespace Rancoud\Session;

/**
 * Trait Encryption.
 */
trait Encryption
{
    protected ?string $key = null;

    protected string $method = 'aes-256-cbc';

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @param string $method
     *
     * @throws SessionException
     */
    public function setMethod(string $method): void
    {
        if (!\in_array($method, $this->getAvailableMethods(), true)) {
            throw new SessionException(\sprintf('Unknown method: %s', $method));
        }

        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getAvailableMethods(): array
    {
        $ciphers = \openssl_get_cipher_methods();
        $ciphersAndAliases = \openssl_get_cipher_methods(true);
        $cipherAliases = \array_diff($ciphersAndAliases, $ciphers);

        $ciphers = \array_filter($ciphers, static function ($n) {
            $excludeMethods = [
                'ecb', 'des', 'rc2', 'rc4', 'md5',
                '-ocb', '-ccm', '-gcm', '-wrap'
            ];
            foreach ($excludeMethods as $excludeMethod) {
                if (\mb_stripos($n, $excludeMethod) !== false) {
                    return false;
                }
            }

            return true;
        });

        $cipherAliases = \array_filter($cipherAliases, static function ($c) {
            $excludeMethods = [
                'des', 'rc2', '-wrap'
            ];

            if (\PHP_MAJOR_VERSION >= 8 && \PHP_MINOR_VERSION >= 1) {
                $excludeMethods[] = 'id-';
            }

            foreach ($excludeMethods as $excludeMethod) {
                if (\mb_stripos($c, $excludeMethod) !== false) {
                    return false;
                }
            }

            return true;
        });

        $methods = \array_merge($ciphers, $cipherAliases);

        return \array_filter($methods, static function ($c) {
            $forbiddenMethods = [
                'aes-128-cbc-hmac-sha1', 'aes-256-cbc-hmac-sha1'
            ];

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

            return !\in_array(\mb_strtolower($c), $forbiddenMethods, true);
        });
    }

    /**
     * @param string $data
     *
     * @throws SessionException
     *
     * @return string
     */
    public function decrypt(string $data): string
    {
        $this->throwExceptionIfKeyEmpty();

        if ($data === '') {
            return '';
        }

        [$encryptedData, $iv] = \explode('::', \base64_decode($data, true), 2);

        $dataDecrypted = \openssl_decrypt($encryptedData, $this->method, $this->key, 0, $iv);
        if ($dataDecrypted === false) {
            // @codeCoverageIgnoreStart
            /* Could not reach this statement without mocking the function
             */
            throw new SessionException('Could not decrypt with openssl_decrypt');
            // @codeCoverageIgnoreEnd
        }

        return $dataDecrypted;
    }

    /**
     * @param string $data
     *
     * @throws SessionException
     *
     * @return string
     */
    public function encrypt(string $data): string
    {
        $this->throwExceptionIfKeyEmpty();
        $length = false;

        try {
            $length = \openssl_cipher_iv_length($this->method);
            if ($length === false || $length < 1) {
                throw new SessionException('IV generation failed');
            }
        } catch (\Exception $e) {
            throw new SessionException('IV generation failed');
        }

        /** @noinspection CryptographicallySecureRandomnessInspection */
        $iv = \openssl_random_pseudo_bytes($length, $cstrong);
        if ($iv === false || $cstrong === false) {
            // @codeCoverageIgnoreStart
            /* Could not reach this statement without mocking the function
             */
            throw new SessionException('IV generation failed');
            // @codeCoverageIgnoreEnd
        }

        $encrypted = \openssl_encrypt($data, $this->method, $this->key, 0, $iv);

        return \base64_encode($encrypted . '::' . $iv);
    }

    /**
     * @throws SessionException
     */
    protected function throwExceptionIfKeyEmpty(): void
    {
        if ($this->key === null || $this->key === '') {
            throw new SessionException('Key has to be a non-empty string');
        }
    }
}
