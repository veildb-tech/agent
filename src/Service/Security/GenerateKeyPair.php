<?php

declare(strict_types=1);

namespace App\Service\Security;

use Exception;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class GenerateKeyPair extends AbstractSecurity
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
        protected readonly Filesystem $filesystem,
        ?string $secretKey,
        ?string $publicKey,
        ?string $passphrase,
        string $algorithm
    ) {
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
        $this->passphrase = $passphrase;
        $this->algorithm = $algorithm;
    }

    /**
     * Generate Keys
     *
     * @param string $id
     * @param bool $keyOnly
     * @param SymfonyStyle $inputOutput
     *
     * @return array
     * @throws Exception
     */
    public function execute(string $id, bool $keyOnly, SymfonyStyle $inputOutput): array
    {
        $this->validateConfigs();

        if ($key = $this->getKeyById($id, $this->publicKey)) {
            if (!$keyOnly
                && !$inputOutput->confirm(
                    sprintf("The key with ID: %s already exists. Do you want to regenerate it?", $id))
            ) {
                return ['', "-----BEGIN PUBLIC KEY-----\n" . $key . "\n-----END PUBLIC KEY-----"];
            }

            $this->clearFile($id, $this->publicKey);
            $this->clearFile($id, $this->secretKey);
        }

        [$secretKey, $publicKey] = $this->generateKeyPair($this->passphrase);

        $this->storeKeyPair($id, $secretKey, $publicKey);

        return [$secretKey, $publicKey];
    }

    /**
     * Check configs before start
     *
     * @return void
     * @throws LogicException
     */
    private function validateConfigs(): void
    {
        if (!in_array($this->algorithm, self::ACCEPTED_ALGORITHMS, true)) {
            throw new LogicException(
                sprintf('Cannot generate key pair with the provided algorithm `%s`.', $this->algorithm)
            );
        }

        if (!$this->secretKey || !$this->publicKey) {
            throw new LogicException(
                'The "app.secret_key_private" and "app.secret_key_public" '
                . 'config options must not be empty for using the command.',
            );
        }
    }

    /**
     * Store keys into file
     *
     * @param string $id
     * @param string $secretKey
     * @param string $publicKey
     *
     * @return void
     */
    private function storeKeyPair(string $id, string $secretKey, string $publicKey): void
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
            $this->filesystem->dumpFile($this->secretKey, $id . ':' . $secretKey . "\n");
            $this->filesystem->dumpFile($this->publicKey, $id . ':' . $publicKey . "\n");

            return;
        }

        $this->filesystem->appendToFile($this->secretKey, $id . ':' . $secretKey . "\n");
        $this->filesystem->appendToFile($this->publicKey, $id . ':' . $publicKey . "\n");
    }

    /**
     * @param string|null $passphrase
     *
     * @return array
     */
    private function generateKeyPair(?string $passphrase): array
    {
        $config = $this->buildOpenSSLConfiguration($this->algorithm);

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
}
