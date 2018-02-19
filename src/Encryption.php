<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Exception;

/**
 * Trait Encrypted.
 */
trait Encryption
{
    protected $key;
    protected $method = 'aes-256-cbc';

    /**
     * @param $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @param string $method
     *
     * @throws Exception
     */
    public function setMethod(string $method): void
    {
        if (!in_array($method, $this->getAvailableMethods(), true)) {
            throw new Exception('Method unkwoned');
        }

        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getAvailableMethods(): array
    {
        $ciphers = openssl_get_cipher_methods();
        $ciphersAndAliases = openssl_get_cipher_methods(true);
        $cipherAliases = array_diff($ciphersAndAliases, $ciphers);

        $ciphers = array_filter($ciphers, function ($n) {
            return mb_stripos($n, 'ecb') === false;
        });
        $ciphers = array_filter($ciphers, function ($c) {
            return mb_stripos($c, 'des') === false;
        });
        $ciphers = array_filter($ciphers, function ($c) {
            return mb_stripos($c, 'rc2') === false;
        });
        $ciphers = array_filter($ciphers, function ($c) {
            return mb_stripos($c, 'rc4') === false;
        });
        $ciphers = array_filter($ciphers, function ($c) {
            return mb_stripos($c, 'md5') === false;
        });
        $ciphers = array_filter($ciphers, function ($c) {
            return mb_stripos($c, '-ocb') === false;
        });
        $ciphers = array_filter($ciphers, function ($c) {
            return mb_stripos($c, '-ccm') === false;
        });
        $ciphers = array_filter($ciphers, function ($c) {
            return mb_stripos($c, '-gcm') === false;
        });
        $ciphers = array_filter($ciphers, function ($c) {
            return mb_stripos($c, '-wrap') === false;
        });

        $cipherAliases = array_filter($cipherAliases, function ($c) {
            return mb_stripos($c, 'des') === false;
        });
        $cipherAliases = array_filter($cipherAliases, function ($c) {
            return mb_stripos($c, 'rc2') === false;
        });
        $cipherAliases = array_filter($cipherAliases, function ($c) {
            return mb_stripos($c, '-wrap') === false;
        });

        return array_merge($ciphers, $cipherAliases);
    }

    /**
     * @param $data
     *
     * @throws \Exception
     *
     * @return string
     */
    public function decrypt(string $data): string
    {
        $this->throwExceptionIfKeyEmpty();

        list($encrypted_data, $iv) = explode('::', base64_decode($data, true), 2);

        return openssl_decrypt($encrypted_data, $this->method, $this->key, 0, $iv);
    }

    /**
     * @param $data
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function encrypt(string $data): string
    {
        $this->throwExceptionIfKeyEmpty();

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
        $encrypted = openssl_encrypt($data, $this->method, $this->key, 0, $iv);
        $data = base64_encode($encrypted . '::' . $iv);

        return $data;
    }

    /**
     * @throws \Exception
     */
    protected function throwExceptionIfKeyEmpty()
    {
        if (mb_strlen($this->key) === 0) {
            throw new Exception('Key is empty');
        }
    }
}
