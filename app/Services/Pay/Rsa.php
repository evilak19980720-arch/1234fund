<?php

namespace App\Services\Pay;


class Rsa
{
    public string $publicKey;
    public string $privateKey;
    private $_privKey;

    /**
     * * private key
     */
    private $_pubKey;

    /**
     * * the construtor,the param $path is the keys saving path
     * @param string|null $publicKey 公钥
     * @param string|null $privateKey 私钥
     */
    public function __construct(string $publicKey = null, string $privateKey = null)
    {
        $this->setKey($publicKey, $privateKey);
    }

    /**
     * 设置公钥和私钥
     * @param string|null $publicKey 公钥
     * @param string|null $privateKey 私钥
     */
    public function setKey(string $publicKey = null, string $privateKey = null)
    {
        if (!is_null($publicKey)) {
            $this->publicKey = $publicKey;
        }
        if (!is_null($privateKey)) {
            $this->privateKey = $privateKey;
        }
    }

    /**
     * * setup the private key
     */
    private function setupPrivateKey(): void
    {
        if (is_resource($this->_privKey)) {
            return;
        }
        $pem = chunk_split($this->privateKey, 64, "\n");
        $pem = "-----BEGIN PRIVATE KEY-----\n" . $pem . "-----END PRIVATE KEY-----\n";
        $this->_privKey = openssl_pkey_get_private($pem);
    }

    /**
     * * setup the public key
     */
    private function setupPublicKey(): void
    {
        if (is_resource($this->_pubKey)) {
            return;
        }
        $pem = chunk_split($this->publicKey, 64, "\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
        $this->_pubKey = openssl_pkey_get_public($pem);
    }

    /**
     * * encrypt with the private key
     */
    public function privateEncrypt($data): ?string
    {
        if (!is_string($data)) {
            return null;
        }
        $this->setupPrivateKey();
        $r = openssl_private_encrypt($data, $encrypted, $this->_privKey);
        if ($r) {
            return base64_encode($encrypted);
        }
        return null;
    }

    /**
     * * decrypt with the private key
     */
    public function privateDecrypt($encrypted)
    {
        if (!is_string($encrypted)) {
            return null;
        }
        $this->setupPrivateKey();
        $encrypted = base64_decode($encrypted);
        $r = openssl_private_decrypt($encrypted, $decrypted, $this->_privKey);
        if ($r) {
            return $decrypted;
        }
        return null;
    }

    /**
     * * encrypt with public key
     */
    public function publicEncrypt($data): ?string
    {
        if (!is_string($data)) {
            return null;
        }
        $this->setupPublicKey();
        $r = openssl_public_encrypt($data, $encrypted, $this->_pubKey);
        if ($r) {
            return base64_encode($encrypted);
        }
        return null;
    }

    /**
     * * decrypt with the public key
     */
    public function publicDecrypt($crypt)
    {
        if (!is_string($crypt)) {
            return null;
        }
        $this->setupPublicKey();
        $crypt = base64_decode($crypt);
        $r = openssl_public_decrypt($crypt, $decrypted, $this->_pubKey);
        if ($r) {
            return $decrypted;
        }
        return null;
    }

    /**
     * 构造签名
     * @param string $dataString 被签名数据
     * @return string
     */
    public function sign(string $dataString ,$algorithm = OPENSSL_ALGO_SHA256): string
    {
        $this->setupPrivateKey();
        $signature = false;
        openssl_sign($dataString, $signature, $this->_privKey, $algorithm);
        return base64_encode($signature);
    }

    /**
     * 验证签名
     * @param string $dataString 被签名数据
     * @param string $signString 已经签名的字符串
     * @return false|int 1签名正确 0签名错误
     */
    public function verify(string $dataString, string $signString,$algorithm = OPENSSL_ALGO_SHA256)
    {
        $this->setupPublicKey();
        $signature = base64_decode($signString);
        return openssl_verify($dataString, $signature, $this->_pubKey, $algorithm); //OPENSSL_ALGO_SHA256
    }

    public function __destruct()
    {
        is_resource($this->_privKey) && @openssl_free_key($this->_privKey);
        is_resource($this->_pubKey) && @openssl_free_key($this->_pubKey);
    }
}
