<?php

declare(strict_types=1);

namespace App\Service\Methods;

use App\Exception\ConnectionError;
use App\Exception\DumpNotFoundException;
use App\Service\InputOutput;
use \Exception;

class Sftp extends AbstractMethod
{
    const AUTH_TYPE_KEY = 'key';
    const AUTH_TYPE_PASS = 'password';
    const AUTH_TYPE_NONE = 'none';

    private $connection;

    /**
     * @param array $dbConfig
     * @param string $dbUuid
     * @param string|null $filename
     * @return string
     * @throws Exception
     */
    public function execute(array $dbConfig, string $dbUuid, ?string $filename = null): string
    {
        $destFile = $this->getDestinationFile($dbUuid, $filename);
        ssh2_scp_recv($this->getConnection($dbConfig), $dbConfig['sftp_filepath'], $destFile);

        return $destFile;
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'sftp';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Download backup from remote server by SSH/SFTP';
    }

    /**
     * @param array $config
     * @return bool
     * @throws Exception
     */
    public function validate(array $config): bool
    {
        $connection = $this->getConnection($config);
        if (!$connection) {
            throw new ConnectionError(
                "Couldn't connect to remote SFTP server. Please ensure credentials as correct"
            );
        }

        $fileExists = file_exists('ssh2.sftp://' . $connection . $config['sftp_filepath']);
        if (!$fileExists) {
            throw new DumpNotFoundException(sprintf("Couldn't find find file %s", $config['sftp_filepath']));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function askConfig(InputOutput $inputOutput, array $config = []): array
    {
        $newConfig = [];

        $newConfig['sftp_host'] = $inputOutput->ask(
            "SFTP Host", $config['sftp_host'] ?? null, self::validateRequired(...)
        );
        $newConfig['sftp_port'] = $inputOutput->ask(
            "SFTP Port", $config['sftp_port'] ?? '22', self::validateRequired(...)
        );
        $newConfig['sftp_user'] = $inputOutput->ask(
            "SFTP User", $config['sftp_user'] ?? null, self::validateRequired(...)
        );

        $newConfig['sftp_auth'] = $inputOutput->choice(
            "Select authentication method:",
            [
                self::AUTH_TYPE_KEY => 'SSH Key',
                self::AUTH_TYPE_PASS => 'Password',
                self::AUTH_TYPE_NONE => 'None',
            ],
            $config['sftp_auth'] ?? null,
        );

        if ($newConfig['sftp_auth'] === self::AUTH_TYPE_KEY) {
            $newConfig['sftp_public_key_path'] = $inputOutput->ask(
                'Path to public key', $config['sftp_public_key_path'] ?? '~/.ssh/id_rsa.pub', self::validateRequired(...)
            );

            $newConfig['sftp_private_key_path'] = $inputOutput->ask(
                'Path to private key', $config['sftp_private_key_path'] ?? '~/.ssh/id_rsa', self::validateRequired(...)
            );

            $newConfig['sftp_key_passphrase'] = $inputOutput->ask('Passphrase');
        } elseif ($newConfig['sftp_auth'] === self::AUTH_TYPE_PASS) {
            $newConfig['sftp_password'] = $this->encryptor->encrypt(
                $inputOutput->askHidden('SFTP Password', self::validateRequired(...))
            );
        }

        $newConfig['sftp_filepath'] = $inputOutput->ask(
            "Full path to backup", $config['sftp_filepath'] ?? null, self::validateRequired(...)
        );

        unset($config);
        return $newConfig;
    }

    /**
     * @param array $config
     *
     * @return resource
     * @throws ConnectionError
     * @throws Exception
     */
    private function getConnection(array $config)
    {
        if ($this->connection === null) {
            $connection = ssh2_connect($config['sftp_host'], (int)$config['sftp_port']);

            if ($config['sftp_auth'] === self::AUTH_TYPE_KEY) {
                ssh2_auth_pubkey_file(
                    $connection,
                    $config['sftp_user'],
                    $config['sftp_public_key_path'],
                    $config['sftp_private_key_path'],
                    $config['sftp_key_passphrase'] ?? ''
                );
            } elseif ($config['sftp_auth'] === self::AUTH_TYPE_PASS) {
                ssh2_auth_password(
                    $connection,
                    $config['sftp_user'],
                    $this->encryptor->decrypt($config['sftp_password'])
                );
            } elseif ($config['sftp_auth'] === self::AUTH_TYPE_NONE) {
                ssh2_auth_none($connection, $config['sftp_user']);
            } else {
                throw new Exception(sprintf("No such auth method %s", $config['sftp_auth']));
            }

            if (!$connection) {
                throw new ConnectionError("Can't connect to sftp server");
            }

            $this->connection = $connection;
        }

        return $this->connection;
    }
}
