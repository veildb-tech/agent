<?php

declare(strict_types=1);

namespace App\Service;

use Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Exception;

class AppConfig
{
    /**
     * Internal docker backups folder
     */
    public const LOCAL_BACKUPS_FOLDER = '/app/backups/local_backups/';

    /**
     * @var array
     */
    private array $databaseConfig = [];

    /**
     * Get DB engine configurations
     *
     * @var array
     */
    private array $dbEngineConfig = [];

    private ?array $defaultConfig = null;

    /**
     * @param KernelInterface $kernel
     * @param Filesystem $filesystem
     * @param array $config
     */
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly Filesystem $filesystem,
        array $config
    ) {
        $this->defaultConfig = $config;
    }

    /**
     * Get server UUID param
     *
     * @return array|string|false
     */
    public function getServerUuid(): array|string|false
    {
        return env('APP_SERVER_UUID', '');
    }

    /**
     * Get server Secret Key
     *
     * @return array|string|false
     */
    public function getServerSecretKey(): array|string|false
    {
        return env('APP_SERVER_SECRET_KEY', '');
    }

    /**
     * Get Docker Server URL
     *
     * @return string
     */
    public function getDockerServerUrl(): string
    {
        return env('APP_DOCKER_SERVER_URL', '');
    }

    /**
     * Check is tool based on Docker env
     *
     * @return bool
     */
    public function isDockerUsed(): bool
    {
        return (bool)env('APP_DOCKER_USE', false);
    }

    /**
     * Get Docker Network Gateway
     *
     * @return string
     */
    public function getDockerGateway(): string
    {
        $subnet = env('DBVISOR_SUBNET', '172.27.0.0/16');
        [$ip, $mask] = explode('/', $subnet);

        return long2ip((ip2long($ip) & ((pow(2, $mask) - 1) << (32 - $mask))) + 1);
    }

    /**
     * @param string $dbUuid
     *
     * @return array
     * @throws Exception
     */
    public function getDatabaseConfig(string $dbUuid): array
    {
        if (!isset($this->databaseConfig[$dbUuid]) || empty($this->databaseConfig[$dbUuid])) {
            $this->databaseConfig[$dbUuid] = array_change_key_case(
                $this->getConfigFile($this->getConfigDirectory() . '/' . $dbUuid)
            );
        }

        return $this->databaseConfig[$dbUuid] ?? [];
    }

    /**
     * Get configuration by DB engine
     *
     * @param string $config
     * @param string $engine
     *
     * @return string|null
     * @throws Exception
     */
    public function getDbEngineConfig(string $config, string $engine = 'mysql'): ?string
    {
        if (!isset($this->dbEngineConfig[$engine])) {
            if (!$this->filesystem->exists($this->getProjectDir() . '/.env.' . $engine)) {
                throw new Exception("Can't allocate database configurations.");
            }

            $this->dbEngineConfig[$engine] = array_change_key_case(
                $this->getConfigFile($this->getProjectDir(), '.env.' . $engine)
            );
        }

        return $this->dbEngineConfig[$engine][strtolower($config)] ?? null;
    }

    /**
     * @param string $config
     * @return string|null
     */
    public function getConfig(string $config): null | string
    {
        return $this->defaultConfig[strtolower($config)] ?? null;
    }

    /**
     * Get DB password
     *
     * @param string $engine
     *
     * @return string
     * @throws Exception
     */
    public function getPassword(string $engine = 'mysql'): string
    {
        return $this->getDbEngineConfig('database_password', $engine)
            ? $this->getDbEngineConfig('database_password', $engine)
            : '';
    }

    /**
     * @param array $databaseConfig
     *
     * @return void
     * @throws Exception
     */
    public function saveDatabaseConfig(array $databaseConfig): void
    {
        if (empty($databaseConfig['db_uuid'])) {
            throw new Exception("Can't allocate database. Please ensure token is valid");
        }

        $databaseConfigFile = $this->getConfigDirectory() . '/' . $databaseConfig['db_uuid'] . '/config';
        if ($this->filesystem->exists($databaseConfigFile)) {
            $this->filesystem->remove($databaseConfigFile);
        }

        $this->filesystem->mkdir($this->getConfigDirectory() . '/' . $databaseConfig['db_uuid']);

        foreach ($databaseConfig as $key => $value) {
            $this->filesystem->appendToFile($databaseConfigFile, sprintf("%s='%s'\n", strtoupper($key), $value));
        }

        $this->databaseConfig[$databaseConfig['db_uuid']] = $databaseConfig;
    }

    /**
     * @return string
     */
    public function getDumpUntouchedDirectory(): string
    {
        return $this->getAppDumpDir() . '/untouched';
    }

    /**
     * @return string
     */
    public function getDumpProcessedDirectory(): string
    {
        return $this->getAppDumpDir() . '/processed';
    }

    /**
     * @return string
     */
    public function getKeyFilePath(): string
    {
        return $this->getConfigDirectory() . '/keys';
    }

    /**
     * Get configuration directory
     * In case docker used the path must be: <project root : app>/backups/configs
     *
     * @return string
     */
    public function getConfigDirectory(): string
    {
        if ($this->isDockerUsed()) {
            return rtrim($this->getProjectDir(), '/') . '/backups/configs';
        }

        $path = trim(env('APP_CONFIG_PATH'))
            ? trim(env('APP_CONFIG_PATH')) : rtrim($this->getProjectDir(), '/') . '/backups/configs';

        return rtrim($path, '/');
    }

    /**
     * Get Dump Dir
     * In case docker used the path must be: <project root : app>/backups/backups
     *
     * @return string
     */
    public function getAppDumpDir(): string
    {
        if ($this->isDockerUsed()) {
            return rtrim($this->getProjectDir(), '/') . '/backups/backups';
        }

        $path = !empty(trim(env('APP_DUMP_PATH')))
            ? trim(env('APP_DUMP_PATH')) : rtrim($this->getProjectDir(), '/') . '/backups/backups';

        return rtrim($path, '/');
    }

    /**
     * Get path to local backups
     *
     * @return string
     */
    public function getLocalBackupsDir(): string
    {
        $path = !empty(trim(env('APP_DOCKER_LOCAL_BACKUPS_PATH')))
            ? trim(env('APP_DOCKER_LOCAL_BACKUPS_PATH'))
            : rtrim($this->getProjectDir(), '/') . '/backups/local_backups';

        return rtrim($path, '/');
    }

    /**
     * Retrieve whole application root directory (not symfony)
     *
     * @return string
     */
    public function getAppRootDir(): string
    {
        return $this->getProjectDir() . '/../..';
    }

    /**
     * Get Project dir
     *
     * @return string
     */
    public function getProjectDir(): string
    {
        return $this->kernel->getProjectDir();
    }

    /**
     * Update / reload configs from env file
     *
     * @return void
     */
    public function updateEnvConfigs(): void
    {
        (new \Symfony\Component\Dotenv\Dotenv())->usePutenv()->bootEnv($this->getProjectDir() . '/.env');
    }

    /**
     * @param string $directory
     * @param string $file
     *
     * @return array
     */
    private function getConfigFile(string $directory, string $file = 'config'): array
    {
        $dotenv = Dotenv::createImmutable($directory, $file);

        return $dotenv->safeLoad();
    }
}
