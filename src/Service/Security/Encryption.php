<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Exception\EncryptionException;

class Encryption
{
    public function __construct(
        private readonly string $secretKey = ''
    ) {
    }

    /**
     * Decrypt data
     *
     * @param string $encryptedData
     *
     * @return string
     * @throws EncryptionException
     */
    public function execute(string $encryptedData): string
    {
        if (!$decryptedData = $this->decrypt($encryptedData)) {
            throw new EncryptionException("Couldn't encrypt data");
        }
        return $decryptedData;
    }

    /**
     * Decrypt data
     *
     * @param string $encryptedData
     *
     * @return string|null
     */
    private function decrypt(string $encryptedData): ?string
    {
        $keys = $this->getKeys();

        foreach ($keys as $key) {
            $key = explode(':', $key);
            if (empty($key[1])) {
                continue;
            }

            $key = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n" . $key[1] . "\n-----END ENCRYPTED PRIVATE KEY-----";
            if (openssl_private_decrypt($encryptedData, $decryptedData, trim($key))) {
                return $decryptedData;
            }
        }
        return null;
    }

    /**
     * Retrieve private keys
     *
     * @return array
     */
    private function getKeys(): array
    {
        return explode("\n", file_get_contents($this->secretKey));
    }
}
