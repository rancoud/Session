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
                'AES-128-CBC-HMAC-SHA1', 'AES-256-CBC-HMAC-SHA1', 'AES-128-CBC-CTS', 'AES-128-CBC-HMAC-SHA256',
                'aes-128-cbc-hmac-sha1', 'aes-256-cbc-hmac-sha1', 'aes-128-cbc-cts', 'aes-128-cbc-hmac-sha256'
            ];

            return !\in_array($c, $forbiddenMethods, true);
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

        /** @noinspection CryptographicallySecureRandomnessInspection */
        $iv = \openssl_random_pseudo_bytes(\openssl_cipher_iv_length($this->method), $cstrong);
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
