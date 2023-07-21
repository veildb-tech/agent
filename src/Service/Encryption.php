<?php

namespace App\Service;

use App\Exception\EncryptionException;

class Encryption
{
    public function __construct(
        private readonly string $keyFile = '',
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
        $keys = $this->getKeys();
        $decrypted = false;
        $decryptedData = '';

        foreach ($keys as $key) {
            if (empty($key)) continue;
            $key = "-----BEGIN PRIVATE KEY-----\n" . $key . "\n-----END PRIVATE KEY-----";
            if (openssl_private_decrypt($encryptedData, $decryptedData, trim($key))) {
                $decrypted = true;
            }
        }

        if (!$decrypted) {
            throw new EncryptionException("Couldn't encrypt data");
        }

        return $decryptedData;
    }

    /**
     * Retrieve private keys
     *
     * @return array
     */
    private function getKeys(): array
    {
        return explode("\n", file_get_contents($this->keyFile));
    }
}
