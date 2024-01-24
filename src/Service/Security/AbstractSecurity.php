<?php

declare(strict_types=1);

namespace App\Service\Security;

abstract class AbstractSecurity
{
    /**
     * @param string $newId
     * @param string $file
     *
     * @return string|null
     */
    protected function getKeyById(string $newId, string $file): ?string
    {
        $rows = $this->readFile($file);
        foreach ($rows as $row) {
            [$id, $key] = explode(':', $row);
            if ($id == $newId) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Remove string from file
     *
     * @param string $id
     * @param string $fileName
     *
     * @return void
     */
    protected function clearFile(string $id, string $fileName): void
    {
        $key = $this->getKeyById($id, $fileName);
        $this->filesystem->dumpFile(
            $fileName,
            str_replace($id . ":" . $key . "\n", '', file_get_contents($fileName))
        );
    }

    /**
     * Read file with keys
     *
     * @param string $file
     *
     * @return array
     */
    protected function readFile(string $file): array
    {
        if (!$this->filesystem->exists($file)) {
            return [];
        }
        return array_filter(explode("\n", file_get_contents($file)));
    }

    /**
     * Builds the OpenSSL configuration based on the given algorithm
     *
     * @param string $algorithm The algorithm for which the configuration is built
     *
     * @return array The OpenSSL configuration
     */
    protected function buildOpenSSLConfiguration(string $algorithm): array
    {
        $digestAlgorithms = [
            'RS256' => 'sha256',
            'RS384' => 'sha384',
            'RS512' => 'sha512',
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
            'ES256' => 'sha256',
            'ES384' => 'sha384',
            'ES512' => 'sha512',
        ];

        $privateKeyBits = [
            'RS256' => 2048,
            'RS384' => 2048,
            'RS512' => 4096,
            'HS256' => 512,
            'HS384' => 512,
            'HS512' => 512,
            'ES256' => 384,
            'ES384' => 512,
            'ES512' => 1024,
        ];

        $privateKeyTypes = [
            'RS256' => \OPENSSL_KEYTYPE_RSA,
            'RS384' => \OPENSSL_KEYTYPE_RSA,
            'RS512' => \OPENSSL_KEYTYPE_RSA,
            'HS256' => \OPENSSL_KEYTYPE_DH,
            'HS384' => \OPENSSL_KEYTYPE_DH,
            'HS512' => \OPENSSL_KEYTYPE_DH,
            'ES256' => \OPENSSL_KEYTYPE_EC,
            'ES384' => \OPENSSL_KEYTYPE_EC,
            'ES512' => \OPENSSL_KEYTYPE_EC,
        ];

        $curves = [
            'ES256' => 'secp256k1',
            'ES384' => 'secp384r1',
            'ES512' => 'secp521r1',
        ];

        $config = [
            'digest_alg' => $digestAlgorithms[$algorithm],
            'private_key_type' => $privateKeyTypes[$algorithm],
            'private_key_bits' => $privateKeyBits[$algorithm],
        ];

        if (isset($curves[$algorithm])) {
            $config['curve_name'] = $curves[$algorithm];
        }

        return $config;
    }
}
