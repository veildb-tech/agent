<?php

declare(strict_types=1);

namespace App\Service\Security;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Filesystem\Filesystem;

final class GenerateKeyPair
{
    private const ACCEPTED_ALGORITHMS = [
        'RS256',
        'RS384',
        'RS512',
        'HS256',
        'HS384',
        'HS512',
        'ES256',
        'ES384',
        'ES512',
    ];

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var string|null
     */
    private ?string $secretKey;

    /**
     * @var string|null
     */
    private ?string $publicKey;

    /**
     * @var string|null
     */
    private ?string $passphrase;

    /**
     * @var string
     */
    private string $algorithm;

    /**
     * @param Filesystem $filesystem
     * @param string|null $secretKey
     * @param string|null $publicKey
     * @param string|null $passphrase
     * @param string $algorithm
     */
    public function __construct(
        Filesystem $filesystem,
        ?string $secretKey,
        ?string $publicKey,
        ?string $passphrase,
        string $algorithm
    ) {
        $this->filesystem = $filesystem;
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
        $this->passphrase = $passphrase;
        $this->algorithm = $algorithm;
    }

    /**
     * Generate Keys
     *
     * @param string $identifier
     *
     * @return array
     * @throws \Exception
     */
    public function execute(string $identifier): array
    {
        if (!in_array($this->algorithm, self::ACCEPTED_ALGORITHMS, true)) {
            throw new \Exception(
                sprintf('Cannot generate key pair with the provided algorithm `%s`.', $this->algorithm)
            );
        }

        [$secretKey, $publicKey] = $this->generateKeyPair($this->passphrase);

        if (!$this->secretKey || !$this->publicKey) {
            throw new LogicException(
                'The "app.secret_key_private" and "app.secret_key_public" '
                . 'config options must not be empty for using the command.',
            );
        }

        $this->storeKeyPair($identifier, $secretKey, $publicKey);

        return [$secretKey, $publicKey];
    }

    /**
     * Store keys into file
     *
     * @param string $identifier
     * @param string $secretKey
     * @param string $publicKey
     *
     * @return void
     */
    private function storeKeyPair(string $identifier, string $secretKey, string $publicKey): void
    {
        $alreadyExists = $this->filesystem->exists($this->secretKey) || $this->filesystem->exists($this->publicKey);

        $secretKey = str_replace(
            [
                '-----BEGIN ENCRYPTED PRIVATE KEY-----',
                '-----END ENCRYPTED PRIVATE KEY-----',
                "\n",
                "\r\n"
            ],
            '',
            $secretKey
        );

        $publicKey = str_replace(
            [
                '-----BEGIN PUBLIC KEY-----',
                '-----END PUBLIC KEY-----',
                "\n",
                "\r\n"
            ],
            '',
            $publicKey
        );

        if (!$alreadyExists) {
            $this->filesystem->dumpFile($this->secretKey, $identifier . ':' . $secretKey);
            $this->filesystem->dumpFile($this->publicKey, $identifier . ':' . $publicKey);
        } else {
            $this->filesystem->appendToFile($this->secretKey, "\n" . $identifier . ':' . $secretKey);
            $this->filesystem->appendToFile($this->publicKey, "\n" . $identifier . ':' . $publicKey);
        }
    }

    private function generateKeyPair($passphrase): array
    {
        $config = $this->buildOpenSSLConfiguration();

        $resource = \openssl_pkey_new($config);
        if (false === $resource) {
            throw new \RuntimeException(\openssl_error_string());
        }

        $success = \openssl_pkey_export($resource, $privateKey, $passphrase);

        if (false === $success) {
            throw new \RuntimeException(\openssl_error_string());
        }

        $publicKeyData = \openssl_pkey_get_details($resource);

        if (false === $publicKeyData) {
            throw new \RuntimeException(\openssl_error_string());
        }

        $publicKey = $publicKeyData['key'];

        return [$privateKey, $publicKey];
    }

    private function buildOpenSSLConfiguration(): array
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
            'digest_alg' => $digestAlgorithms[$this->algorithm],
            'private_key_type' => $privateKeyTypes[$this->algorithm],
            'private_key_bits' => $privateKeyBits[$this->algorithm],
        ];

        if (isset($curves[$this->algorithm])) {
            $config['curve_name'] = $curves[$this->algorithm];
        }

        return $config;
    }
}
