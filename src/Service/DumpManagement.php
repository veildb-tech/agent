<?php

declare(strict_types=1);

namespace App\Service;

use App\ServiceApi\Entity\DatabaseDump;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\File;
use App\Exception\NoSuchMethodException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use App\Service\Methods\MethodFactory;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DumpManagement
{
    const DEFAULT_FILE_PERMISSION = 0777;

    /**
     * @param DatabaseDump $databaseDump
     * @param AppConfig $appConfig
     * @param MethodFactory $methodFactory
     */
    public function __construct(
        private readonly DatabaseDump $databaseDump,
        private readonly AppConfig $appConfig,
        private readonly MethodFactory $methodFactory
    ) {
    }

    /**
     * @param string $dbUuid
     * @param string|null $filename
     *
     * @return File
     * @throws NoSuchMethodException
     */
    public function createDump(string $dbUuid, ?string $filename = null): File
    {
        $this->initDumpDirectories($dbUuid);
        $dbConfig = $this->appConfig->getDatabaseConfig($dbUuid);
        if (!$dbConfig) {
            throw new \Exception("Couldn't find database config");
        }
        $method = $this->methodFactory->create($dbConfig['method']);
        $dumpFile = $method->execute($dbConfig, $dbUuid, $filename);

        if (!is_file($dumpFile)) {
            throw new FileNotFoundException("Something went wrong during backup creation. File not found");
        }

        return new File($dumpFile);
    }

    /**
     * @param string $uuid
     *
     * @return File|null
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function getDumpFileByUuid(string $uuid): ?File
    {
        $dump = $this->getDumpByUuid($uuid);

        if (empty($dump['filename'])) {
            return null;
        }

        if (empty($dump['db'])) {
            throw new Exception("Couldn't allocate database for dump");
        }
        return new File($this->appConfig->getDumpProcessedDirectory() . '/' . $dump['db']['uid'] . '/' . $dump['filename']);
    }

    /**
     * @param string $uuid
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getDumpByUuid(string $uuid): array
    {
        return $this->databaseDump->getByUuid($uuid);
    }

    /**
     * @param string $dbUuid
     * @param string $engine
     * @return File
     */
    public function getDestinationFilePath(string $dbUuid, string $engine = 'mysql'): File
    {
        // Should be depended on engine;
        $extension = '.sql';
        $filepath = $this->appConfig->getDumpProcessedDirectory() . '/' . $dbUuid . '/' . time() . $extension;
        return new File($filepath, false);
    }

    /**
     * @param string $dbuid
     * @return void
     */
    public function initDumpDirectories(string $dbuid): void
    {
        $untouchedDir = $this->appConfig->getDumpUntouchedDirectory() . '/' . $dbuid;
        $processedDir = $this->appConfig->getDumpProcessedDirectory() . '/' . $dbuid;
        if (!is_dir($untouchedDir)) {
            mkdir($untouchedDir, self::DEFAULT_FILE_PERMISSION, true);
        }

        if (!is_dir($processedDir)) {
            mkdir($processedDir, self::DEFAULT_FILE_PERMISSION, true);
        }
    }
}
