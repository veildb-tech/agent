<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle\Service\Methods;

use App\Service\InputOutput;
use DbManager\MysqlBundle\Service\Engine\Mysql as MysqlEngine;
use \Exception;

/**
 * TODO: maybe better to use ssh2_shell to connect by SSH instead of Process
 */
class MysqldumpOverSsh extends \App\Service\Methods\AbstractMethod
{
    const AUTH_TYPE_KEY = 'key';
    const AUTH_TYPE_PASS = 'password';

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
        $dbPassword = !empty($dbConfig['db_password']) ? sprintf('-p%s', $dbConfig['db_password']) : '';
        $mysqlCommand = sprintf(
            "mysqldump -u %s %s -h%s -P%s %s",
            $dbConfig['db_user'],
            $dbPassword,
            $dbConfig['db_host'],
            $dbConfig['db_port'],
            $dbConfig['db_name']
        );

        $sshCommand = $this->prepareSshCommand($dbConfig);
        $this->shellProcess->run(sprintf('%s "%s" > %s', $sshCommand, $mysqlCommand, $destFile));

        return $destFile;
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'ssh-dump';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Database located at remote server. Dump over SSH';
    }

    /**
     * @param array $config
     * @return bool
     * @throws Exception
     */
    public function validate(array $config): bool
    {
        $dbPassword = !empty($config['db_password']) ? sprintf('-p%s', $config['db_password']) : '';
        $mysqlCommand = sprintf(
            "mysql -u%s -h%s %s %s -e 'SELECT 1'",
            $config['db_user'],
            $config['db_host'],
            $dbPassword,
            $config['db_name']
        );

        $sshCommand = $this->prepareSshCommand($config);

        $process = $this->shellProcess->run(sprintf('%s "%s"', $sshCommand, $mysqlCommand));

        return str_replace("\n", "", $process->getOutput()) === '11';
    }

    /**
     * @inheritDoc
     */
    public function askConfig(InputOutput $inputOutput, array $config = []): array
    {
        return [
            ...$this->askDatabaseConfig($inputOutput, $config),
            ...$this->askSSHConfig($inputOutput, $config)
        ];
    }

    /**
     * Generate shell string to connect to SSH from config
     *
     * @param array $config
     * @return string
     * @throws Exception
     */
    private function prepareSshCommand(array $config): string
    {
        if ($config['ssh_auth'] === self::AUTH_TYPE_PASS) {
            $sshCommand = sprintf(
                "sshpass -p '%s' ssh -o 'StrictHostKeyChecking=no' %s@%s -p %s",
                $this->encryptor->decrypt($config['ssh_password']),
                $config['ssh_user'],
                $config['ssh_host'],
                $config['ssh_port']
            );
        } elseif ($config['ssh_auth'] === self::AUTH_TYPE_KEY) {
            $sshCommand = sprintf(
                "ssh -i %s %s@%s -p %s",
                $config['ssh_key_path'],
                $config['ssh_user'],
                $config['ssh_host'],
                $config['ssh_port']
            );
        } else {
            throw new \Exception(sprintf("Undefined authentication type %s", $config['ssh_auth']));
        }

        return $sshCommand;
    }

    /**
     * Ask for SSH credentials
     *
     * @param InputOutput $inputOutput
     * @param array $config
     *
     * @return array
     */
    private function askSSHConfig(InputOutput $inputOutput, array $config = []): array
    {
        $newConfig = [];
        $validateRequired = function ($value) {
            if (empty($value)) {
                throw new \RuntimeException('Value is required.');
            }
            return $value;
        };

        $newConfig['ssh_host'] = $inputOutput->ask(
            'SSH Host:', $config['ssh_host'] ?? '', $validateRequired
        );
        $newConfig['ssh_user'] = $inputOutput->ask(
            'SSH User:', $config['ssh_user'] ?? '', $validateRequired
        );
        $newConfig['ssh_auth'] = $inputOutput->choice(
            "Select authentication method:",
            [
                self::AUTH_TYPE_KEY => 'SSH Key',
                self::AUTH_TYPE_PASS => 'Password'
            ],
            $config['ssh_auth'] ?? null,
        );

        if ($newConfig['ssh_auth'] === self::AUTH_TYPE_KEY) {
            $newConfig['ssh_key_path'] = $inputOutput->ask(
                'Key path:', $config['ssh_key_path'] ?? '~/.ssh/id_rsa', $validateRequired
            );
        } elseif ($newConfig['ssh_auth'] === self::AUTH_TYPE_PASS) {
            $newConfig['ssh_password'] = $this->encryptor->encrypt(
                $inputOutput->askHidden('SSH Password', $validateRequired)
            );
        } else {
            $inputOutput->error("Something went wrong. Method is not specified");
            exit;
        }

        $newConfig['ssh_port'] = $inputOutput->ask(
            'SSH Port:', $config['ssh_port'] ?? '22', $validateRequired
        );

        return $newConfig;
    }

    /**
     * @param InputOutput $inputOutput
     * @param array $config
     *
     * @return array
     */
    private function askDatabaseConfig(InputOutput $inputOutput, array $config = []): array
    {
        $newConfig = [];
        $newConfig['db_host'] = $inputOutput->ask(
            'Database Host', $config['db_host'] ?? 'localhost', self::validateRequired(...)
        );
        $newConfig['db_user'] = $inputOutput->ask(
            'Database User:', $config['db_user'] ?? 'root', self::validateRequired(...)
        );
        $newConfig['db_password'] = $inputOutput->askHidden('Password');
        $newConfig['db_name'] = $inputOutput->ask(
            'Database name', $config['db_name'] ?? null, self::validateRequired(...)
        );
        $newConfig['db_port'] = $inputOutput->ask(
            'Database Port: ', $config['db_port'] ?? '3306', self::validateRequired(...)
        );
        return $newConfig;
    }

    /**
     * @param string $engine
     * @return bool
     */
    public function support(string $engine): bool
    {
        return $engine === MysqlEngine::ENGINE_CODE;
    }
}
