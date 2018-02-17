<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Exception;

/**
 * Trait Encrypted.
 */
trait Encrypted
{
    protected $key;

    /**
     * @param $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @param $data
     *
     * @return string
     * @throws \Exception
     */
    public function decrypt(string $data): string
    {
        $this->throwExceptionIfKeyEmpty();

        list($encrypted_data, $iv) = explode('::', base64_decode($data, true), 2);

        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $this->key, 0, $iv);
        //return mcrypt_decrypt(MCRYPT_3DES, $this->key, $data, MCRYPT_MODE_ECB);
    }

    /**
     * @param $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function encrypt(string $data): string
    {
        $this->throwExceptionIfKeyEmpty();

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->key, 0, $iv);
        $data = base64_encode($encrypted . '::' . $iv);
        //$data = mcrypt_encrypt(MCRYPT_3DES, $this->key, $data, MCRYPT_MODE_ECB);

        return $data;
    }

    /**
     * @throws \Exception
     */
    protected function throwExceptionIfKeyEmpty(){
        if(mb_strlen($this->key) === 0){
            throw new Exception('Key is empty');
        }
    }
}
