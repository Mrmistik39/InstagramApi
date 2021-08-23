<?php

class Encrypt{

    public $password, $publicKey, $keyId;

    public function __construct($password, $publicKey, $keyId)
    {
        $this->password = $password;
        $this->publicKey = $publicKey;
        $this->keyId = $keyId;
    }

    public function encrypt()
    {
        $time = time();
        $key = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(12);
        $aesEncrypted = openssl_encrypt($this->password, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, strval($time));
        $encryptedKey = sodium_crypto_box_seal($key, hex2bin($this->publicKey));
        return [
            'time' => $time,
            'encrypted' => base64_encode("\x01" | pack('n', intval($this->keyId)) . pack('s', strlen($encryptedKey)) . $encryptedKey . $tag . $aesEncrypted)
        ];
    }

    public function generateEncPassword($version=10)
    {
        $result = $this->encrypt();
        return '#PWD_INSTAGRAM_BROWSER:' . $version . ':' . $result['time'] . ':' . $result['encrypted'];
    }

}
