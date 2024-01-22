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

/**
 * Class AddDatabase
 *
 * The AddDatabase class is responsible for adding a new database to the system.
 */
final class AddDatabase extends AbstractDatabaseCommand
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

        $this->addDatabase();
    }

    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function addDatabase(): void
    {
        $this->config = [];

        $inputOutput = $this->getInputOutput();

        if (!$inputOutput->confirm("Would you like to add new database?")) {
            return;
        }

        $server = $this->serverApi->get($this->appConfig->getServerUuid());

        $this->poll();

        if (!$this->checkConnection()) {
            $this->handleInvalidConnection($server);
            return;
        } else {
            $this->saveDbtoService($server);
        }

        $this->addDatabase();
    }
}
