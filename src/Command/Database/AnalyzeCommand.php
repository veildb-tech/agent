<?php

declare(strict_types=1);

namespace App\Command\Database;

use App\Service\PublicCommand\Database\Analyzer;
use DbManager\CoreBundle\Exception\EngineNotSupportedException;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use Doctrine\DBAL\Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:db:analyze',
    description: 'Start analyzing db structure',
)]
final class AnalyzeCommand extends Command
{
    /**
     * @param Analyzer $databaseAnalyzer
     * @param LoggerInterface   $logger
     * @param string|null       $name
     */
    public function __construct(
        protected readonly Analyzer $databaseAnalyzer,
        protected readonly LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->addOption(
            'uid',
            'u',
            InputOption::VALUE_REQUIRED,
            'Database UUID from the service'
        )->addOption(
            'db',
            null,
            InputOption::VALUE_OPTIONAL,
            'Temporary database name'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->databaseAnalyzer->execute(
                $input,
                $output
            );
        } catch (
            ClientExceptionInterface
            | RedirectionExceptionInterface
            | ServerExceptionInterface
            | EngineNotSupportedException
            | DecodingExceptionInterface
            | TransportExceptionInterface
            | NoSuchEngineException $e
        ) {
            $this->logger->error($e->getMessage());

            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
