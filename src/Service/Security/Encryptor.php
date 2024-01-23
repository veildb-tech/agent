<?php

declare(strict_types=1);

namespace App\Service\Security;

use Symfony\Component\Filesystem\Filesystem;

final class Encryptor extends AbstractSecurity
{
    /**
     * @param Filesystem $filesystem
     * @param string $secretKey
     * @param string $algorithm
     */
    public function __construct(
        protected readonly Filesystem $filesystem,
        protected readonly string $secretKey = '',
        protected readonly string $algorithm = ''
    ) {
    }

    public function encrypt(string $string): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->algorithm));
        return openssl_encrypt($string, $this->algorithm, $this->secretKey, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Decrypt data
     *
     * @param string $encryptedData
     *
     * @return string|null
     */
    public function decrypt(string $encryptedData): ?string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->algorithm));
        return openssl_decrypt($encryptedData, $this->algorithm, $this->secretKey, OPENSSL_RAW_DATA, $iv);
    }

    public function encryptWithKey()
    {

    }

    public function decryptWithKey(string $encryptedData)
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
