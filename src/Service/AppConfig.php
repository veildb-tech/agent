<?php

declare(strict_types=1);

namespace App\Service;

use Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Exception;

class AppConfig
{
    private array $databaseConfig = [];

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
     * @param string $dbUuid
     *
     * @return array
     * @throws Exception
     */
    public function getDatabaseConfig(string $dbUuid): array
    {
        if (empty($this->databaseConfig[$dbUuid])) {
            $this->databaseConfig[$dbUuid] = array_change_key_case(
                $this->getConfigFile($this->getConfigDirectory() . '/' . $dbUuid)
            );
        }

        return $this->databaseConfig[$dbUuid] ?? [];
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
     * @param array $databaseConfig
     * @return void
     * @throws Exception
     */
    public function saveDatabaseConfig(array $databaseConfig): void
    {
        if (empty($databaseConfig['db_uuid'])) {
            throw new Exception("Can't allocate database. Please ensure token is valid");
        }

        $databaseConfigDirectory = $this->getConfigDirectory() . '/' . $databaseConfig['db_uuid'];
        $databaseConfigFile = $this->getConfigDirectory() . '/' . $databaseConfig['db_uuid'] . '/config';

        $this->filesystem->mkdir($databaseConfigDirectory);

        foreach ($databaseConfig as $key => $value) {
            $this->filesystem->appendToFile($databaseConfigFile, sprintf("%s=%s\n", strtoupper($key), $value));
        }
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
     *
     * @return string
     */
    public function getConfigDirectory(): string
    {
        $path = trim(env('APP_CONFIG_PATH')) ? trim(env('APP_CONFIG_PATH')) : $this->getProjectDir();

        return rtrim($path, '/') . '/config';
    }

    /**
     * Get Dump Dir
     *
     * @return string
     */
    public function getAppDumpDir(): string
    {
        $path = !empty(trim(env('APP_DUMP_PATH'))) ? trim(env('APP_DUMP_PATH')) : $this->getProjectDir();

        return rtrim($path, '/') . '/dumps';
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
