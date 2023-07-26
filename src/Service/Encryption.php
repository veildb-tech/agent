<?php

namespace App\Service;

use App\Exception\EncryptionException;

class Encryption
{
    public function __construct(
        private readonly string $keyPath = '',
        private readonly string $keyFile = ''
    ) {
    }

    /**
     * Try to decrypt data
     *
     * @param string $encryptedData
     * @return string
     * @throws EncryptionException
     */
    public function decrypt(string $encryptedData): string
    {
        $decrypted = false;
        $decryptedData = '';

        if ($privateKey = $this->getPrivateKey()) {
            $res = openssl_get_privatekey($privateKey, '');
            if (openssl_private_decrypt($encryptedData, $decryptedData, $res)) {
                $decrypted = true;
            }
        }

        if (!$decrypted) {
            throw new EncryptionException("Couldn't encrypt data");
        }

        return $decryptedData;
    }

    private function getPrivateKey(): ?string
    {
        $fp = fopen(rtrim($this->keyPath, '/') . '/' . $this->keyFile, "r");
        $privateKey = fread($fp, 8192);
        fclose($fp);

        return $privateKey;
    }
}
