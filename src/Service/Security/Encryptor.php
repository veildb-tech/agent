<?php

declare(strict_types=1);

namespace App\Service\Security;

use Exception;
use Symfony\Component\Filesystem\Filesystem;

final class Encryptor extends AbstractSecurity
{
    /**
     * @param Filesystem $filesystem
     * @param string $publicKey
     * @param string $privateKey
     * @param string $secretKey
     * @param string $algorithm
     */
    public function __construct(
        protected readonly Filesystem $filesystem,
        protected readonly string $publicKey = '',
        protected readonly string $privateKey = '',
        protected readonly string $secretKey = '',
        protected readonly string $algorithm = '',
        protected readonly string $cipherAlgo = ''
    ) {
    }

    /**
     * Encrypt data
     *
     * @param string $string
     *
     * @return string
     * @throws Exception
     */
    public function encrypt(string $string): string
    {
        $config = $this->buildOpenSSLConfiguration($this->algorithm);

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipherAlgo));
        $encrypted = openssl_encrypt($string, $this->cipherAlgo, $this->secretKey, OPENSSL_RAW_DATA, $iv);

        if (false === $encrypted) {
            throw new Exception(openssl_error_string());
        }

        $hmac = hash_hmac($config['digest_alg'], $encrypted, $this->secretKey, true);
        return base64_encode($iv . $hmac . $encrypted);
    }

    /**
     * Decrypt data
     *
     * @param string $encryptedData
     *
     * @return string|null
     * @throws Exception
     */
    public function decrypt(string $encryptedData): ?string
    {
        $config = $this->buildOpenSSLConfiguration($this->algorithm);
        $encryptedData = base64_decode($encryptedData);

        $ivlen = openssl_cipher_iv_length($this->cipherAlgo);
        $iv = substr($encryptedData, 0, $ivlen);
        $hmac = substr($encryptedData, $ivlen, $sha2len = 32);


        $encryptedDataRaw = substr($encryptedData, $ivlen + $sha2len);
        $decrypted = openssl_decrypt($encryptedDataRaw, $this->cipherAlgo, $this->secretKey, OPENSSL_RAW_DATA, $iv);
        if (false === $decrypted) {
            throw new Exception(openssl_error_string());
        }

        if (!hash_equals($hmac, hash_hmac($config['digest_alg'], $encryptedDataRaw, $this->secretKey, true))) {
            throw new Exception("Decrypted data is not match.");
        }
        return $decrypted;
    }

    /**
     * Encrypt the given string with a specific key.
     *
     * @param string $string The string to be encrypted.
     * @param string $id The id to retrieve the key. Defaults to 'server'.
     *
     * @return string The encrypted string.
     * @throws Exception If the data could not be encrypted.
     */
    public function encryptWithKey(string $string, string $id = 'server'): string
    {
        if (!$key = $this->getKeyById($id, $this->publicKey)) {
            throw new Exception(sprintf('The key with id: %s not found.', $id));
        }

        if (!openssl_public_encrypt(
            $string,
            $encryptedData,
            $this->formKey($key, 'PUBLIC KEY'))
        ) {
            throw new Exception('The data could not be encrypted.');
        }

        return $encryptedData;
    }

    /**
     * Decrypts the given encrypted data using the secret key.
     *
     * @param string $encryptedData The encrypted data to be decrypted.
     *
     * @return string|null The decrypted data if decryption is successful, null otherwise.
     */
    public function decryptWithKey(string $encryptedData): ?string
    {
        $rows = $this->readFile($this->privateKey);
        foreach ($rows as $row) {
            [$id, $key] = explode(':', $row);
            if (empty($key)) {
                continue;
            }

            if (openssl_private_decrypt(
                $encryptedData,
                $decryptedData,
                $this->formKey($key, 'ENCRYPTED PRIVATE KEY'))
            ) {
                return $decryptedData;
            }
        }
        return null;
    }

    /**
     * Forms a key string with the specified key and keyType.
     *
     * @param string $key The key value.
     * @param string $keyType The type of key.
     *
     * @return string The formed key string.
     */
    private function formKey(string $key, string $keyType): string
    {
        return trim("-----BEGIN {$keyType}-----\n" . $key . "\n-----END {$keyType}-----");
    }
}
