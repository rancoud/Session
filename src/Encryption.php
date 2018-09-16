<?php

declare(strict_types=1);

namespace Rancoud\Session;

/**
 * Trait Encryption.
 */
trait Encryption
{
    /** @var string */
    protected $key;

    /** @var string */
    protected $method = 'aes-256-cbc';

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
            throw new SessionException(\sprintf('Method unknowed: %s', $method));
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

        $ciphers = \array_filter($ciphers, function ($n) {
            return \mb_stripos($n, 'ecb') === false;
        });
        $ciphers = \array_filter($ciphers, function ($c) {
            return \mb_stripos($c, 'des') === false;
        });
        $ciphers = \array_filter($ciphers, function ($c) {
            return \mb_stripos($c, 'rc2') === false;
        });
        $ciphers = \array_filter($ciphers, function ($c) {
            return \mb_stripos($c, 'rc4') === false;
        });
        $ciphers = \array_filter($ciphers, function ($c) {
            return \mb_stripos($c, 'md5') === false;
        });
        $ciphers = \array_filter($ciphers, function ($c) {
            return \mb_stripos($c, '-ocb') === false;
        });
        $ciphers = \array_filter($ciphers, function ($c) {
            return \mb_stripos($c, '-ccm') === false;
        });
        $ciphers = \array_filter($ciphers, function ($c) {
            return \mb_stripos($c, '-gcm') === false;
        });
        $ciphers = \array_filter($ciphers, function ($c) {
            return \mb_stripos($c, '-wrap') === false;
        });

        $cipherAliases = \array_filter($cipherAliases, function ($c) {
            return \mb_stripos($c, 'des') === false;
        });
        $cipherAliases = \array_filter($cipherAliases, function ($c) {
            return \mb_stripos($c, 'rc2') === false;
        });
        $cipherAliases = \array_filter($cipherAliases, function ($c) {
            return \mb_stripos($c, '-wrap') === false;
        });

        $methods = \array_merge($ciphers, $cipherAliases);

        $methods = \array_filter($methods, function ($c) {
            $forbiddenMethods = ['AES-128-CBC-HMAC-SHA1', 'AES-256-CBC-HMAC-SHA1',
                                'aes-128-cbc-hmac-sha1', 'aes-256-cbc-hmac-sha1'];

            return !\in_array($c, $forbiddenMethods, true);
        });

        return $methods;
    }

    /**
     * @param string $data
     *
     * @throws SessionException
     *
     * @return string|bool
     */
    public function decrypt(string $data)
    {
        $this->throwExceptionIfKeyEmpty();

        if ($data === '') {
            return '';
        }

        list($encrypted_data, $iv) = \explode('::', \base64_decode($data, true), 2);

        return \openssl_decrypt($encrypted_data, $this->method, $this->key, 0, $iv);
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

        $iv = \openssl_random_pseudo_bytes(\openssl_cipher_iv_length($this->method));
        $encrypted = \openssl_encrypt($data, $this->method, $this->key, 0, $iv);
        $data = \base64_encode($encrypted . '::' . $iv);

        return $data;
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
