<?php

declare(strict_types=1);

namespace App\Action;

use App\Service\AppConfig;
use App\ServiceApi\Entity\DatabaseDump;
use App\ServiceApi\Entity\Server;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class ClearBackupsAction
{
    /**
     * @param AppConfig $appConfig
     * @param Server $server
     * @param DatabaseDump $databaseDump
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly AppConfig $appConfig,
        private readonly Server $server,
        private readonly DatabaseDump $databaseDump,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function execute(): void
    {
        $backups = $this->getDumpsList();
        foreach ($backups as $backup) {
            $file = $this->appConfig->getDumpProcessedDirectory()
                . '/'
                . $backup['db_uuid']
                . '/'
                . $backup['filename'];

            if ($this->filesystem->exists($file)) {
                $this->filesystem->remove($file);

                $this->databaseDump->delete($backup['uuid']);
            }
        }
    }

    /**
     * Get list for dump for deleting
     *
     * @return array
     * @throws InvalidArgumentException
     */
    private function getDumpsList(): array
    {
        try {
            return $this->server->getDbDumpsForDelete($this->appConfig->getServerUuid());
        } catch (
            ClientExceptionInterface
            | DecodingExceptionInterface
            | RedirectionExceptionInterface
            | ServerExceptionInterface
            | TransportExceptionInterface $e
        ) {
            $this->logger->error($e->getMessage());

            return [];
        }
    }
}
