<?php

declare(strict_types=1);

namespace App\Service\PublicCommand\Database;

use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class UpdateDatabase extends AbstractDatabaseCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->appLogger->initAppLogger($output);
        $this->initInputOutput($input, $output);

        if (!$databaseUid = $input->getOption('uid')) {
            $databaseUid = $this->getDatabaseUid();
        }

        $this->config = $this->getDbInfo($databaseUid);

        $server = $this->serverApi->get($this->appConfig->getServerUuid());

        $this->poll();

        if (!$this->checkConnection()) {
            $this->handleInvalidConnection($server);
        } else {
            $this->saveDbtoService($server);
        }
    }

    /**
     * Get DB Uid from list
     *
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    protected function getDatabaseUid(): string
    {
        $databaseList = $this->databaseApi->getList();

        if (!count($databaseList)) {
            throw new Exception(
                "We do not find databases assigned to this server."
                . " Contact our support if you still face this problem."
            );

        }

        return $this->getInputOutput()->choice(
            "Please select database",
            array_combine(
                array_column($databaseList, 'uid'),
                array_column($databaseList, 'name')
            )
        );
    }

    /**
     * Retrieves the database information for the specified database UID.
     *
     * @param string $databaseUid The UID of the database to retrieve information for.
     *                           Must be a non-empty string.
     *
     * @return array
     * @throws \Exception If an unexpected error occurs during the retrieval process.
     */
    private function getDbInfo(string $databaseUid): array
    {
        try {
            $dbData = $this->appConfig->getDatabaseConfig($databaseUid);
            if (!$dbData['platform']) {
                $dbData['platform'] = 'custom';
            }

            if (!isset($dbData['db_uuid'])) {
                $dbData['db_uuid'] = $databaseUid;
            }

            return $dbData;
        } catch (\Exception $exp) {
            throw new Exception(
                "We do not find an information by selected Db UUID."
                . " Contact our support if you still face this problem."
            );
        }
    }
}
