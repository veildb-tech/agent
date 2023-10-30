<?php

declare(strict_types=1);

namespace App\Service\Methods;

use App\Service\InputOutput;
use \Exception;

/**
 * TODO: maybe better to use ssh2_shell to connect by SSH instead of Process
 */
class DumpOverSsh extends AbstractMethod
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
    public function askConfig(InputOutput $inputOutput): array
    {
        return [
            ...$this->askDatabaseConfig($inputOutput),
            ...$this->askSSHConfig($inputOutput)
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
                "sshpass -p '%s' ssh %s@%s -p %s",
                $config['ssh_password'],
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
     * @return array
     */
    private function askSSHConfig(InputOutput $inputOutput): array
    {
        $validateRequired = function ($value) {
            if (empty($value)) {
                throw new \RuntimeException('Value is required.');
            }

            return $value;
        };
        $config = [];

        $config['ssh_host'] = $inputOutput->ask('SSH Host:', '', $validateRequired);
        $config['ssh_user'] = $inputOutput->ask('SSH User:', '', $validateRequired);
        $config['ssh_auth'] = $inputOutput->choice("Select authentication method:", [
            self::AUTH_TYPE_KEY => 'SSH Key',
            self::AUTH_TYPE_PASS => 'Password'
        ]);

        if ($config['ssh_auth'] === self::AUTH_TYPE_KEY) {
            $config['ssh_key_path'] = $inputOutput->ask('Key path:', '~/.ssh/id_rsa', $validateRequired);
        } elseif ($config['ssh_auth'] === self::AUTH_TYPE_PASS) {
            $config['ssh_password'] = $inputOutput->askHidden('SSH Password:', $validateRequired);
        } else {
            $inputOutput->error("Something went wrong. Method is not specified");
            exit;
        }

        $config['ssh_port'] = $inputOutput->ask('SSH Port:', '22', $validateRequired);

        return $config;
    }

    /**
     * @param InputOutput $inputOutput
     * @return array
     */
    private function askDatabaseConfig(InputOutput $inputOutput): array
    {
        $config = [];

        $config['db_host'] = $inputOutput->ask('Database Host', 'localhost', self::validateRequired(...));
        $config['db_user'] = $inputOutput->ask('Database User:', 'root', self::validateRequired(...));
        $config['db_password'] = $inputOutput->askHidden('Password');
        $config['db_name'] = $inputOutput->ask('Database name:', null, self::validateRequired(...));
        $config['db_port'] = $inputOutput->ask('Database Port: ', '3306', self::validateRequired(...));

        return $config;
    }
}
