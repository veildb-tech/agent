<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Exception\EncryptionException;

final class Encryption extends AbstractSecurity
{
    /**
     * @param string $secretKey
     */
    public function __construct(
        protected readonly string $secretKey = ''
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
        $rows = $this->readFile($this->secretKey);
        foreach ($rows as $row) {
            [$id, $key] = explode(':', $row);
            if (empty($key)) {
                continue;
            }

            $key = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n" . $key . "\n-----END ENCRYPTED PRIVATE KEY-----";
            if (openssl_private_decrypt($encryptedData, $decryptedData, trim($key))) {
                return $decryptedData;
            }
        }
        return null;
    }
}
