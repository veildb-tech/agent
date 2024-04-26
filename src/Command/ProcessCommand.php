<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\NoSuchMethodException;
use App\Service\PublicCommand\DatabaseProcessor;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:db:process',
    description: 'Start processing database by database id and temporary database name',
)]
final class ProcessCommand extends Command
{
    /**
     * @param DatabaseProcessor $databaseProcessor
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        protected readonly DatabaseProcessor $databaseProcessor,
        protected readonly LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->databaseProcessor->execute($input, $output);
        } catch (
            ClientExceptionInterface
            | RedirectionExceptionInterface
            | ServerExceptionInterface
            | DecodingExceptionInterface
            | TransportExceptionInterface $e
        ) {
            $this->logger->error($e->getMessage());

            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
