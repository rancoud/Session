<?php

declare(strict_types=1);

namespace Rancoud\Session;

/**
 * Class Encrypted.
 */
class Encrypted
{
    protected $key;

    /**
     * @param $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function read($id)
    {
        $data = parent::read($id);

        list($encrypted_data, $iv) = explode('::', base64_decode($data, true), 2);

        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $this->key, 0, $iv);
        //return mcrypt_decrypt(MCRYPT_3DES, $this->key, $data, MCRYPT_MODE_ECB);
    }

    /**
     * @param $id
     * @param $data
     *
     * @return mixed
     */
    public function write($id, $data)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->key, 0, $iv);
        $data = base64_encode($encrypted . '::' . $iv);
        //$data = mcrypt_encrypt(MCRYPT_3DES, $this->key, $data, MCRYPT_MODE_ECB);

        return parent::write($id, $data);
    }
}
