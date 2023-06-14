<?php

declare(strict_types=1);

namespace App\Command;

use App\ServiceApi\Actions\SendDumpLogs;
use Exception;
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
    name: 'app:db:log',
    description: 'Send logs to service',
)]
final class AddDbLogCommand extends Command
{
    /**
     * @param SendDumpLogs      $sendDumpLogs
     * @param LoggerInterface   $logger
     * @param string|null       $name
     */
    public function __construct(
        protected readonly SendDumpLogs $sendDumpLogs,
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
            'uuid',
            'u',
            InputOption::VALUE_REQUIRED,
            'Dump UUID data'
        )->addOption(
            'status',
            null,
            InputOption::VALUE_REQUIRED,
            'Status'
        )->addOption(
            'message',
            null,
            InputOption::VALUE_REQUIRED,
            'Message'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->sendDumpLogs->execute(
                $input->getOption('uuid'),
                $input->getOption('status'),
                $input->getOption('message')
            );
        } catch (
            Exception
            | ClientExceptionInterface
            | RedirectionExceptionInterface
            | ServerExceptionInterface
            | DecodingExceptionInterface
            | TransportExceptionInterface $e
        ) {
            $output->writeln($e->getMessage());

            $this->logger->error($e->getMessage());

            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
