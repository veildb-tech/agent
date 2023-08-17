<?php

declare(strict_types=1);

namespace App\Command;

use App\Action\ClearBackupsAction;
use App\Service\Database\Analyzer;
use Doctrine\DBAL\Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

#[AsCommand(
    name: 'app:db:backups:clear',
    description: 'Start cleaning old DB backups'
)]
final class AppDbClearBackupsCommand extends Command
{
    /**
     * @param Analyzer $databaseAnalyzer
     * @param ClearBackupsAction $clearBackupsAction
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        protected readonly Analyzer $databaseAnalyzer,
        protected readonly ClearBackupsAction $clearBackupsAction,
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
     * @throws Exception|InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->clearBackupsAction->execute();
        } catch (
            ClientExceptionInterface
            | RedirectionExceptionInterface
            | ServerExceptionInterface $e
        ) {
            $this->logger->error($e->getMessage());

            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
